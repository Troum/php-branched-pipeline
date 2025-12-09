Минимальная реализация шаблона проектирования *Pipeline* для PHP c поддержкой ветвления логики.

Возможности:

* цепочка обработки данных шаг за шагом
* условное ветвление (*if/else*) через `BranchPipe`
* множественный выбор (*switch/case*) через `SwitchPipe`
* легко тестируемые и переиспользуемые шаги (pipes)
* строгая типизация (PHP 8.1+)

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
use Troum\Pipeline\{
    Pipeline,
    PipeInterface
};

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

Позволяет разделить выполнение по условию (например: новый покупатель / постоянный).

```php
use Troum\Pipeline;

$pipeline = (new Pipeline())->via([
    new CalculatePrice(),

    new BranchPipe(
        condition: fn($p) => $p['is_new'] === true,
        isTrueConditionPipes: [new AddWelcomeCoupon()],
        isFalseConditionPipes: [new ApplyLoyaltyDiscount()],
    ),
]);

$result = $pipeline->process([
    'price' => 100,
    'is_new' => true,
]);
```

---

## Множественный выбор (SwitchPipe)

Маршрутизация обработки на основании значения указанного поля.

```php
use Troum\Pipeline;

$pipeline = (new Pipeline())->via([
    new AddTax(),

    new SwitchPipe(
        field: 'customer_type',
        cases: [
            'regular'   => [new ApplyRegularDiscount()],
            'vip'       => [new ApplyVipDiscount()],
            'wholesale' => [new ApplyWholesaleDiscount()],
        ],
        default: []
    ),
]);
```

Если значение поля отсутствует в `cases` — применяется `default`.

---

## Множественный выбор (SwitchPipe)

Маршрутизация обработки на основании перечисляемого значения.

```php
enum CustomerType: string {
    case Regular = 'regular';
    case Vip = 'vip';
    case Wholesale = 'wholesale';
}

$pipeline = (new \Troum\Pipeline\Pipeline())->via([
    new AddTax(),

    new \Troum\Pipeline\EnumSwitchPipe(
        field: 'customer_type',
        cases: [
            CustomerType::Regular => [new ApplyRegularDiscount()],
            CustomerType::Vip => [new ApplyVipDiscount()],
            CustomerType::Wholesale => [new ApplyWholesaleDiscount()],
        ]
    ),
]);

$result = $pipeline->process([
    'price' => 100,
    'customer_type' => CustomerType::Vip,
]);

```

Отличия от `SwitchPipe`

| Особенность                | SwitchPipe             | EnumSwitchPipe                        |
| -------------------------- | ---------------------- | ------------------------------------- |
| Тип ключа                  | строка/число           | **backed enum**                       |
| Валидация                  | нет                    | строгая                               |
| Ошибки неправильного ключа | не выявляются          | приводят к исключению                 |
| Идеально для               | API, строковых payload | строго типизированной доменной логики |


---

## Контракт PipeInterface

Каждый pipe обязан реализовать метод:

```php
interface PipeInterface
{
    public function handle(mixed $payload, Closure $next): mixed;
}
```

Рекомендуется использовать неизменяемые DTO (по возможности).

---

## Лицензия

MIT. Полностью свободное использование.

