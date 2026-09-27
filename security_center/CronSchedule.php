<?php

final class WbceSecurityCenterCronSchedule
{
    public static function fromInput($input)
    {
        $mode = (($input['schedule_mode'] ?? 'rate') === 'calendar') ? 'calendar' : 'rate';
        $periodKey = $mode === 'rate' ? 'schedule_period' : 'calendar_period';
        $period = (string)($input[$periodKey] ?? 'day');
        $allowed = array('hour', 'day', 'week', 'month', 'year', 'once');
        if (!in_array($period, $allowed, true)) throw new InvalidArgumentException('Ungültiger Zeitraum.');
        $time = trim((string)($input['schedule_time'] ?? '09:00'));
        if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $match)) throw new InvalidArgumentException('Ungültige Uhrzeit.');
        $hour = min(23, (int)$match[1]);
        $minute = min(59, (int)$match[2]);
        $count = max(1, (int)($input['schedule_count'] ?? 1));
        $limits = array('hour' => 6, 'day' => 12, 'week' => 5, 'month' => 15, 'year' => 6);
        $count = min($limits[$period] ?? 1, $count);
        $weekday = max(0, min(6, (int)($input['schedule_weekday'] ?? 0)));
        $monthday = max(1, min(28, (int)($input['schedule_monthday'] ?? 1)));
        $month = max(1, min(12, (int)($input['schedule_month'] ?? 1)));
        if ($period === 'once') return self::once($input);
        $cron = $mode === 'calendar'
            ? self::calendarCron($period, $minute, $hour, $weekday, $monthday, $month)
            : self::rateCron($period, $count, $minute, $hour, $weekday, $monthday, $month);
        $labels = array('hour' => 'Stunde', 'day' => 'Tag', 'week' => 'Woche', 'month' => 'Monat', 'year' => 'Jahr');
        return array('cron' => $cron, 'configuration' => array(
            'once' => false, 'mode' => $mode, 'period' => $period, 'count' => $count,
            'label' => $count . ' × pro ' . $labels[$period],
        ));
    }

    private static function once($input)
    {
        $runAt = trim((string)($input['run_at'] ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $runAt, self::timezone());
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || (is_array($errors) && ($errors['warning_count'] || $errors['error_count'])) || $date->getTimestamp() <= time()) {
            throw new InvalidArgumentException('Der einmalige Zeitpunkt muss in der Zukunft liegen.');
        }
        return array(
            'cron' => $date->format('i G j n') . ' *',
            'configuration' => array(
                'once' => true,
                'run_at' => $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'label' => 'Einmalig am ' . $date->format('d.m.Y H:i'),
            ),
        );
    }

    private static function calendarCron($period, $minute, $hour, $weekday, $monthday, $month)
    {
        if ($period === 'hour') return $minute . ' * * * *';
        if ($period === 'day') return $minute . ' ' . $hour . ' * * *';
        if ($period === 'week') return $minute . ' ' . $hour . ' * * ' . $weekday;
        if ($period === 'month') return $minute . ' ' . $hour . ' ' . $monthday . ' * *';
        return $minute . ' ' . $hour . ' ' . $monthday . ' ' . $month . ' *';
    }

    private static function rateCron($period, $count, $minute, $hour, $weekday, $monthday, $month)
    {
        if ($period === 'hour') return self::slots(60, $count, $minute) . ' * * * *';
        if ($period === 'day') return $minute . ' ' . self::slots(24, $count, $hour) . ' * * *';
        if ($period === 'week') return $minute . ' ' . $hour . ' * * ' . self::slots(7, $count, $weekday);
        if ($period === 'month') return $minute . ' ' . $hour . ' ' . self::slots(28, $count, $monthday - 1, 1) . ' * *';
        return $minute . ' ' . $hour . ' ' . $monthday . ' ' . self::slots(12, $count, $month - 1, 1) . ' *';
    }

    private static function timezone()
    {
        if (function_exists('wbce_timezone')) {
            try { $timezone = wbce_timezone(); if ($timezone instanceof DateTimeZone) return $timezone; } catch (Throwable $ignored) {}
        }
        if (defined('TIMEZONE_IDENTIFIER')) {
            try { return new DateTimeZone((string)TIMEZONE_IDENTIFIER); } catch (Throwable $ignored) {}
        }
        return new DateTimeZone(date_default_timezone_get());
    }

    private static function slots($span, $count, $anchor, $offset = 0)
    {
        $values = array();
        for ($i = 0; $i < $count; $i++) $values[] = $offset + (($anchor - $offset + (int)floor($i * $span / $count)) % $span);
        sort($values);
        return implode(',', array_unique($values));
    }
}
