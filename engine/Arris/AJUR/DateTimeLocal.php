<?php
/**
 * Created by PhpStorm.
 * User: wombat
 * Date: 04.03.19
 * Time: 15:50
 */

namespace Arris\AJUR;

class DateTimeLocal
{
    public static $tMonth = array(
        '',
        'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
    );

    // old: $tMonthR
    public static $ruMonths = array(
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября',
        10 => 'октября', 11 => 'ноября', 12 => 'декабря'
    );

    public static function getMonth($index)
    {
        return self::$ruMonths[$index] ?? '';
    }

    /**
     * Берёт дату в формате YYYY-MM-DD и возвращает строку "DD месяца YYYY года"
     *
     * @param String $datetime дата YYYY-MM-DD
     * @param bool $is_show_time показывать ли время
     *
     * @return String та же дата, но по-русски
     */
    public static function convertDate($datetime, $is_show_time = false)
    {
        if ($datetime == "0000-00-00 00:00:00" or $datetime == "0000-00-00") return "-";
        list($y, $m, $d, $h, $i, $s) = sscanf($dat, "%d-%d-%d %d:%d:%d");

        $ret = $d . ' ' . self::$ruMonths[$m] .
            ($y ? " $y г." : "");

        if ($is_show_time) {
            $ret .= " " . sprintf("%02d", $h) . ":" . sprintf("%02d", $i) . "";
        }
        return $ret;
    }

    /**
     * Конвертирует дату-время в указанном формате в unix timestamp
     *
     * @param $datetime
     * @param $format
     *
     * @return false|int
     */
    public static function convertDatetimeToTimestamp($datetime, $format = 'd-m-Y H:i:s')
    {
        return (DateTime::createFromFormat($format, $datetime))->format('U');
    }
}

