# troum/php-branched-pipeline

Минимальная реализация шаблона проектирования *Pipeline* для PHP c поддержкой ветвления логики и ранней остановки обработки.

Возможности:

* цепочка обработки данных шаг за шагом
* условное ветвление (*if/else*) через `BranchPipe`
* множественный выбор (*switch/case*) через `SwitchPipe`
* поддержка перечислений (`BackedEnum`) через `EnumSwitchPipe`
* много-ветвевое ветвление через `MultibranchPipe`
* раннее завершение пайплайна с помощью `ShortCircuitPipe`
* строгая типизация (PHP 8.1+)
* удобные методы управления пайпами (`append`, `prepend`, `insertBefore`, `insertAfter`, `clear`)
* универсальный доступ к полям payload (массив, DTO, ArrayAccess, публичные свойства, геттеры)

---

## Установка

Через VCS (например, GitHub):

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/troum/php-branched-pipeline.git"
        }
    ],
    "require": {
        "troum/php-branched-pipeline": "dev-main"
    }
}
```

---

## Базовое использование

Каждый шаг должен реализовать `PipeInterface`.
Пайплайн передаёт данные от pipe к pipe.

```php
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Core\Pipeline;

class AddTax implements PipeInterface
{
    public function handle(mixed $payload, Closure $next): mixed
    {
        $payload['price'] *= 1.2;
        return $next($payload);
    }
}

$pipeline = (new Pipeline())->via([
    new AddTax(),
]);

$result = $pipeline->process(['price' => 100]);
// ['price' => 120]
```

---

## Ветвление по условию (BranchPipe)

```php
use Troum\Pipeline\Core\Pipeline;
use Troum\Pipeline\Pipes\BranchPipe;

$pipeline = (new Pipeline())->via([
    new BranchPipe(
        condition: fn($p) => $p['is_new'] === true,
        isTrueConditionPipes: [new AddWelcomeCoupon()],
        isFalseConditionPipes: [new ApplyLoyaltyDiscount()],
    ),
]);
```

---

## Множественный выбор по полю (SwitchPipe)

```php
use Troum\Pipeline\Core\Pipeline;
use Troum\Pipeline\Pipes\SwitchPipe;
```

---

## EnumSwitchPipe

Маршрутизация по значению BackedEnum

```php
enum CustomerType: string {
    case Regular = 'regular';
    case Vip = 'vip';
    case Wholesale = 'wholesale';
}

$pipeline = (new \Troum\Pipeline\Core\Pipeline())->via([
    new \Troum\Pipeline\Pipes\EnumSwitchPipe(
        field: 'customer_type',
        cases: [
            CustomerType::Regular => [new ApplyRegularDiscount()],
            CustomerType::Vip => [new ApplyVipDiscount()],
            CustomerType::Wholesale => [new ApplyWholesaleDiscount()],
        ]
    ),
]);
```

---

## Мультиветвление (MultibranchPipe)

Поддерживает:

* `MODE_FIRST_MATCH`
* `MODE_ALL_MATCHES`

```php
use Troum\Pipeline\Pipes\MultibranchPipe;
```

---

## Ранняя остановка пайплайна (ShortCircuitPipe)

Если пайплайн должен немедленно завершиться:

```php
use Troum\Pipeline\Pipes\ShortCircuitPipe;
use Troum\Pipeline\Pipes\BranchPipe;

$pipeline = (new Pipeline())->via([
    new BranchPipe(
        condition: fn($p) => $p['blocked'] === true,
        isTrueConditionPipes: [
            new ShortCircuitPipe(fn($p) => [
                'error' => 'User blocked',
                'status' => 'denied',
            ]),
        ],
    ),
    new SomeNextPipe(), // не выполнится, если blocked === true
]);
```

Можно без аргумента:

```php
new ShortCircuitPipe()
```

— payload вернётся как есть.

---

## Управление пайпами

```php
$pipeline
    ->append(new ExtraPipe())
    ->prepend(new InitPipe())
    ->insertBefore($targetPipe, new LoggingPipe())
    ->insertAfter($targetPipe, new ProfilingPipe())
    ->clear();
```

Все методы валидируют, что добавляемые элементы — `PipeInterface`.

---

## Поддерживаемые типы payload при доступе к полям

`SwitchPipe`, `EnumSwitchPipe`, `MultibranchPipe` поддерживают:

| Тип payload                  | Пример доступа                            |
| ---------------------------- | ----------------------------------------- |
| array                        | `$payload['field']`                       |
| ArrayAccess                  | `$payload['field']`                       |
| Объект с публичным свойством | `$payload->field`                         |
| Объект с геттером            | `getField()` / `isField()` / `hasField()` |

При отсутствии поля выбрасывается `InvalidArgumentException`.

---

## Контракт PipeInterface

```php
namespace Troum\Pipeline\Contracts;

use Closure;

interface PipeInterface
{
    public function handle(mixed $payload, Closure $next): mixed;
}
```

---

## Интеграция с Laravel

Для поддержки внедрения зависимостей в pipes через контейнер, а также
разрешения классов-строк в процессе обработки предусмотрен специальный адаптер.

### Подключение

В `config/app.php` добавьте провайдер:

```php
\Troum\Pipeline\Integrations\Laravel\PipelineServiceProvider::class,
````

### Использование

```php
use Troum\Pipeline\Core\Pipeline;

$result = app(Pipeline::class)
    ->via([
        ValidatePipe::class, // резолвится через DI-контейнер
        SavePipe::class,
    ])
    ->process($payload);
```

Все зависимости в конструкторе pipe’ов будут корректно резолвиться через
Laravel Service Container. Pipes создаются **только при вызове**, а не заранее.

---

## Интеграция с Symfony (Lazy Loading)

Symfony-адаптер обеспечивает ленивое создание pipe-объектов через DI-контейнер —
объект будет создан только в случае фактического вызова строки-класса.

### Регистрация сервиса

`config/services.yaml`:

```yaml
services:
    Troum\Pipeline\Integrations\Symfony\SymfonyPipeline:
        arguments:
            - '@service_container'
        public: true
```

### Использование

```php
use Troum\Pipeline\Integrations\Symfony\SymfonyPipeline;

class TestController extends AbstractController
{
    public function index(SymfonyPipeline $pipeline)
    {
        $result = $pipeline
            ->via([
                ValidatePipe::class, // лениво резолвится при выполнении
                BusinessLogicPipe::class,
            ])
            ->process(['price' => 200]);

        return $this->json($result);
    }
}
```

Lazy-loading особенно эффективен при использовании ветвлений —
если pipe не будет вызван из-за условия, он **не создаётся**.


---

## Ограничения

* Payload остаётся `mixed` — строгую типизацию лучше обеспечивать DTO
* Нет встроенного логирования и трассировки (можно реализовать custom-pipe)
* Нет автоматической интеграции с DI-контейнерами (Laravel/Symfony)
* Библиотека намеренно минималистична — не workflow engine

---

## Лицензия

MIT

---
