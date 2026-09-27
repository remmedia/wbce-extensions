<?php
require_once __DIR__.'/Language.php';
final class WbceCronExpression
{
    private array $fields;
    private bool $dayOfMonthWildcard;
    private bool $dayOfWeekWildcard;

    public function __construct(string $expression)
    {
        $parts = preg_split('/\s+/', trim($expression));
        if (count($parts) !== 5) { throw new InvalidArgumentException(worker_t('cron_five_fields')); }
        $ranges = array(array(0, 59), array(0, 23), array(1, 31), array(1, 12), array(0, 7));
        $this->dayOfMonthWildcard = $parts[2] === '*';
        $this->dayOfWeekWildcard = $parts[4] === '*';
        $this->fields = array();
        foreach ($parts as $i => $part) {
            $this->fields[] = $this->parseField($part, $ranges[$i][0], $ranges[$i][1], $i === 4);
        }
    }

    public function matches(DateTimeInterface $date): bool
    {
        $values = array((int)$date->format('i'), (int)$date->format('G'), (int)$date->format('j'), (int)$date->format('n'), (int)$date->format('w'));
        if (!isset($this->fields[0][$values[0]], $this->fields[1][$values[1]], $this->fields[3][$values[3]])) { return false; }
        $monthDayMatches = isset($this->fields[2][$values[2]]);
        $weekDayMatches = isset($this->fields[4][$values[4]]);
        if (!$this->dayOfMonthWildcard && !$this->dayOfWeekWildcard) {
            return $monthDayMatches || $weekDayMatches;
        }
        return $monthDayMatches && $weekDayMatches;
    }

    public function next(DateTimeInterface $after): DateTimeImmutable
    {
        $candidate = DateTimeImmutable::createFromInterface($after)->modify('+1 minute');
        $candidate = $candidate->setTime((int)$candidate->format('H'), (int)$candidate->format('i'), 0);
        for ($i = 0; $i < 2630880; $i++, $candidate = $candidate->modify('+1 minute')) {
            if ($this->matches($candidate)) { return $candidate; }
        }
        throw new RuntimeException(worker_t('cron_no_date'));
    }

    private function parseField(string $field, int $min, int $max, bool $weekday): array
    {
        $result = array();
        foreach (explode(',', $field) as $item) {
            if (!preg_match('/^(\*|\d+)(?:-(\d+))?(?:\/(\d+))?$/', $item, $match)) {
                throw new InvalidArgumentException(worker_t('cron_invalid_field') . $field);
            }
            $start = $match[1] === '*' ? $min : (int)$match[1];
            $end = isset($match[2]) && $match[2] !== '' ? (int)$match[2] : ($match[1] === '*' ? $max : $start);
            $step = isset($match[3]) && $match[3] !== '' ? (int)$match[3] : 1;
            if ($step < 1 || $start < $min || $end > $max || $start > $end) {
                throw new InvalidArgumentException(worker_t('cron_out_of_range') . $item);
            }
            for ($value = $start; $value <= $end; $value += $step) {
                $result[$weekday && $value === 7 ? 0 : $value] = true;
            }
        }
        return $result;
    }
}
