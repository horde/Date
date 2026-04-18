<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTimeZone;

class TimezoneMapper
{
    private static array $aliases = [
        'Dateline Standard Time' => 'Etc/GMT+12',
        'UTC-11' => 'Etc/GMT+11',
        'Hawaiian Standard Time' => 'Pacific/Honolulu',
        'Alaskan Standard Time' => 'America/Anchorage',
        'Pacific Standard Time (Mexico)' => 'America/Santa_Isabel',
        'Pacific Standard Time' => 'America/Los_Angeles',
        'US Mountain Standard Time' => 'America/Phoenix',
        'Mountain Standard Time (Mexico)' => 'America/Chihuahua',
        'Mountain Standard Time' => 'America/Denver',
        'Central America Standard Time' => 'America/Guatemala',
        'Central Standard Time' => 'America/Chicago',
        'Central Standard Time (Mexico)' => 'America/Mexico_City',
        'Canada Central Standard Time' => 'America/Regina',
        'SA Pacific Standard Time' => 'America/Bogota',
        'Eastern Standard Time' => 'America/New_York',
        'US Eastern Standard Time' => 'America/Indianapolis',
        'Venezuela Standard Time' => 'America/Caracas',
        'Paraguay Standard Time' => 'America/Asuncion',
        'Atlantic Standard Time' => 'America/Halifax',
        'Central Brazilian Standard Time' => 'America/Cuiaba',
        'SA Western Standard Time' => 'America/La_Paz',
        'Pacific SA Standard Time' => 'America/Santiago',
        'Newfoundland Standard Time' => 'America/St_Johns',
        'E. South America Standard Time' => 'America/Sao_Paulo',
        'Argentina Standard Time' => 'America/Buenos_Aires',
        'SA Eastern Standard Time' => 'America/Cayenne',
        'Greenland Standard Time' => 'America/Nuuk',
        'Montevideo Standard Time' => 'America/Montevideo',
        'Bahia Standard Time' => 'America/Bahia',
        'UTC-02' => 'Etc/GMT+2',
        'Azores Standard Time' => 'Atlantic/Azores',
        'Cape Verde Standard Time' => 'Atlantic/Cape_Verde',
        'Morocco Standard Time' => 'Africa/Casablanca',
        'GMT Standard Time' => 'Europe/London',
        'Greenwich Standard Time' => 'Atlantic/Reykjavik',
        'W. Europe Standard Time' => 'Europe/Berlin',
        'Central Europe Standard Time' => 'Europe/Budapest',
        'Romance Standard Time' => 'Europe/Paris',
        'Central European Standard Time' => 'Europe/Warsaw',
        'W. Central Africa Standard Time' => 'Africa/Lagos',
        'Namibia Standard Time' => 'Africa/Windhoek',
        'Jordan Standard Time' => 'Asia/Amman',
        'GTB Standard Time' => 'Europe/Bucharest',
        'Middle East Standard Time' => 'Asia/Beirut',
        'Egypt Standard Time' => 'Africa/Cairo',
        'Syria Standard Time' => 'Asia/Damascus',
        'E. Europe Standard Time' => 'Asia/Nicosia',
        'South Africa Standard Time' => 'Africa/Johannesburg',
        'FLE Standard Time' => 'Europe/Kyiv',
        'Turkey Standard Time' => 'Europe/Istanbul',
        'Israel Standard Time' => 'Asia/Jerusalem',
        'Arabic Standard Time' => 'Asia/Baghdad',
        'Kaliningrad Standard Time' => 'Europe/Kaliningrad',
        'Arab Standard Time' => 'Asia/Riyadh',
        'E. Africa Standard Time' => 'Africa/Nairobi',
        'Iran Standard Time' => 'Asia/Tehran',
        'Arabian Standard Time' => 'Asia/Dubai',
        'Azerbaijan Standard Time' => 'Asia/Baku',
        'Russian Standard Time' => 'Europe/Moscow',
        'Mauritius Standard Time' => 'Indian/Mauritius',
        'Georgian Standard Time' => 'Asia/Tbilisi',
        'Caucasus Standard Time' => 'Asia/Yerevan',
        'Afghanistan Standard Time' => 'Asia/Kabul',
        'Pakistan Standard Time' => 'Asia/Karachi',
        'West Asia Standard Time' => 'Asia/Tashkent',
        'India Standard Time' => 'Asia/Calcutta',
        'Sri Lanka Standard Time' => 'Asia/Colombo',
        'Nepal Standard Time' => 'Asia/Katmandu',
        'Central Asia Standard Time' => 'Asia/Almaty',
        'Bangladesh Standard Time' => 'Asia/Dhaka',
        'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg',
        'Myanmar Standard Time' => 'Asia/Yangon',
        'SE Asia Standard Time' => 'Asia/Bangkok',
        'N. Central Asia Standard Time' => 'Asia/Novosibirsk',
        'China Standard Time' => 'Asia/Shanghai',
        'North Asia Standard Time' => 'Asia/Krasnoyarsk',
        'Singapore Standard Time' => 'Asia/Singapore',
        'W. Australia Standard Time' => 'Australia/Perth',
        'Taipei Standard Time' => 'Asia/Taipei',
        'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar',
        'North Asia East Standard Time' => 'Asia/Irkutsk',
        'Tokyo Standard Time' => 'Asia/Tokyo',
        'Korea Standard Time' => 'Asia/Seoul',
        'Cen. Australia Standard Time' => 'Australia/Adelaide',
        'AUS Central Standard Time' => 'Australia/Darwin',
        'E. Australia Standard Time' => 'Australia/Brisbane',
        'AUS Eastern Standard Time' => 'Australia/Sydney',
        'West Pacific Standard Time' => 'Pacific/Port_Moresby',
        'Tasmania Standard Time' => 'Australia/Hobart',
        'Yakutsk Standard Time' => 'Asia/Yakutsk',
        'Central Pacific Standard Time' => 'Pacific/Guadalcanal',
        'Vladivostok Standard Time' => 'Asia/Vladivostok',
        'New Zealand Standard Time' => 'Pacific/Auckland',
        'UTC+12' => 'Etc/GMT-12',
        'Fiji Standard Time' => 'Pacific/Fiji',
        'Magadan Standard Time' => 'Asia/Magadan',
        'Tonga Standard Time' => 'Pacific/Tongatapu',
        'Samoa Standard Time' => 'Pacific/Apia',
        'CET' => 'Europe/Berlin',
        'CST6CDT' => 'America/Chicago',
        'EET' => 'Europe/Athens',
        'EST' => 'America/Panama',
        'EST5EDT' => 'America/New_York',
        'MET' => 'Europe/Berlin',
        'MST' => 'America/Phoenix',
        'MST7MDT' => 'America/Denver',
        'PST8PDT' => 'America/Los_Angeles',
        'WET' => 'Europe/Lisbon',
        'Antarctica/DumontDUrville' => 'Pacific/Port_Moresby',
        'Antarctica/McMurdo' => 'Pacific/Auckland',
        'Antarctica/Syowa' => 'Asia/Riyadh',
        'Australia/Currie' => 'Australia/Hobart',
        'Pacific/Johnston' => 'Pacific/Honolulu',
        'Pacific/Midway' => 'Pacific/Pago_Pago',
        'W. Europe' => 'Europe/Berlin',
        'E. Europe' => 'Asia/Nicosia',
        'Africa/Asmera' => 'Africa/Nairobi',
        'Africa/Timbuktu' => 'Africa/Abidjan',
        'America/Argentina/ComodRivadavia' => 'America/Argentina/Catamarca',
        'America/Atka' => 'America/Adak',
        'America/Buenos_Aires' => 'America/Argentina/Buenos_Aires',
        'America/Catamarca' => 'America/Argentina/Catamarca',
        'America/Coral_Harbour' => 'America/Atikokan',
        'America/Cordoba' => 'America/Argentina/Cordoba',
        'America/Ensenada' => 'America/Tijuana',
        'America/Fort_Wayne' => 'America/Indiana/Indianapolis',
        'America/Godthab' => 'America/Nuuk',
        'America/Indianapolis' => 'America/Indiana/Indianapolis',
        'America/Jujuy' => 'America/Argentina/Jujuy',
        'America/Knox_IN' => 'America/Indiana/Knox',
        'America/Louisville' => 'America/Kentucky/Louisville',
        'America/Mendoza' => 'America/Argentina/Mendoza',
        'America/Montreal' => 'America/Toronto',
        'America/Porto_Acre' => 'America/Rio_Branco',
        'America/Rosario' => 'America/Argentina/Cordoba',
        'America/Santa_Isabel' => 'America/Tijuana',
        'America/Shiprock' => 'America/Denver',
        'America/Virgin' => 'America/Port_of_Spain',
        'Antarctica/South_Pole' => 'Pacific/Auckland',
        'Asia/Ashkhabad' => 'Asia/Ashgabat',
        'Asia/Calcutta' => 'Asia/Kolkata',
        'Asia/Chongqing' => 'Asia/Shanghai',
        'Asia/Chungking' => 'Asia/Shanghai',
        'Asia/Dacca' => 'Asia/Dhaka',
        'Asia/Harbin' => 'Asia/Shanghai',
        'Asia/Kashgar' => 'Asia/Urumqi',
        'Asia/Katmandu' => 'Asia/Kathmandu',
        'Asia/Macao' => 'Asia/Macau',
        'Asia/Rangoon' => 'Asia/Yangon',
        'Asia/Saigon' => 'Asia/Ho_Chi_Minh',
        'Asia/Tel_Aviv' => 'Asia/Jerusalem',
        'Asia/Thimbu' => 'Asia/Thimphu',
        'Asia/Ujung_Pandang' => 'Asia/Makassar',
        'Asia/Ulan_Bator' => 'Asia/Ulaanbaatar',
        'Atlantic/Faeroe' => 'Atlantic/Faroe',
        'Atlantic/Jan_Mayen' => 'Europe/Oslo',
        'Australia/ACT' => 'Australia/Sydney',
        'Australia/Canberra' => 'Australia/Sydney',
        'Australia/LHI' => 'Australia/Lord_Howe',
        'Australia/NSW' => 'Australia/Sydney',
        'Australia/North' => 'Australia/Darwin',
        'Australia/Queensland' => 'Australia/Brisbane',
        'Australia/South' => 'Australia/Adelaide',
        'Australia/Tasmania' => 'Australia/Hobart',
        'Australia/Victoria' => 'Australia/Melbourne',
        'Australia/West' => 'Australia/Perth',
        'Australia/Yancowinna' => 'Australia/Broken_Hill',
        'Brazil/Acre' => 'America/Rio_Branco',
        'Brazil/DeNoronha' => 'America/Noronha',
        'Brazil/East' => 'America/Sao_Paulo',
        'Brazil/West' => 'America/Manaus',
        'Canada/Atlantic' => 'America/Halifax',
        'Canada/Central' => 'America/Winnipeg',
        'Canada/East-Saskatchewan' => 'America/Regina',
        'Canada/Eastern' => 'America/Toronto',
        'Canada/Mountain' => 'America/Edmonton',
        'Canada/Newfoundland' => 'America/St_Johns',
        'Canada/Pacific' => 'America/Vancouver',
        'Canada/Saskatchewan' => 'America/Regina',
        'Canada/Yukon' => 'America/Whitehorse',
        'Chile/Continental' => 'America/Santiago',
        'Chile/EasterIsland' => 'Pacific/Easter',
        'Cuba' => 'America/Havana',
        'Egypt' => 'Africa/Cairo',
        'Eire' => 'Europe/Dublin',
        'Europe/Belfast' => 'Europe/London',
        'Europe/Kiev' => 'Europe/Kyiv',
        'Europe/Tiraspol' => 'Europe/Chisinau',
        'GB' => 'Europe/London',
        'GB-Eire' => 'Europe/London',
        'GMT+0' => 'Etc/GMT',
        'GMT-0' => 'Etc/GMT',
        'GMT0' => 'Etc/GMT',
        'Greenwich' => 'Etc/GMT',
        'Hongkong' => 'Asia/Hong_Kong',
        'Iceland' => 'Atlantic/Reykjavik',
        'Iran' => 'Asia/Tehran',
        'Israel' => 'Asia/Jerusalem',
        'Jamaica' => 'America/Jamaica',
        'Japan' => 'Asia/Tokyo',
        'Kwajalein' => 'Pacific/Kwajalein',
        'Libya' => 'Africa/Tripoli',
        'Mexico/BajaNorte' => 'America/Tijuana',
        'Mexico/BajaSur' => 'America/Mazatlan',
        'Mexico/General' => 'America/Mexico_City',
        'NZ' => 'Pacific/Auckland',
        'NZ-CHAT' => 'Pacific/Chatham',
        'Navajo' => 'America/Denver',
        'PRC' => 'Asia/Shanghai',
        'Pacific/Ponape' => 'Pacific/Pohnpei',
        'Pacific/Samoa' => 'Pacific/Pago_Pago',
        'Pacific/Truk' => 'Pacific/Chuuk',
        'Pacific/Yap' => 'Pacific/Chuuk',
        'Poland' => 'Europe/Warsaw',
        'Portugal' => 'Europe/Lisbon',
        'ROC' => 'Asia/Taipei',
        'ROK' => 'Asia/Seoul',
        'Singapore' => 'Asia/Singapore',
        'Turkey' => 'Europe/Istanbul',
        'UCT' => 'Etc/UCT',
        'US/Alaska' => 'America/Anchorage',
        'US/Aleutian' => 'America/Adak',
        'US/Arizona' => 'America/Phoenix',
        'US/Central' => 'America/Chicago',
        'US/East-Indiana' => 'America/Indiana/Indianapolis',
        'US/Eastern' => 'America/New_York',
        'US/Hawaii' => 'Pacific/Honolulu',
        'US/Indiana-Starke' => 'America/Indiana/Knox',
        'US/Michigan' => 'America/Detroit',
        'US/Mountain' => 'America/Denver',
        'US/Pacific' => 'America/Los_Angeles',
        'US/Samoa' => 'Pacific/Pago_Pago',
        'UTC' => 'UTC',
        'Universal' => 'UTC',
        'W-SU' => 'Europe/Moscow',
        'Zulu' => 'UTC',
    ];

    /** @var array<string, string> */
    private static array $runtimeAliases = [];

    /** @var array<string, int>|null */
    private static ?array $timezoneIdentifiers = null;

    /** @var array<string, list<array{dst: bool, offset: int, timezone_id: string}>>|null */
    private static ?array $timezoneAbbreviations = null;

    public static function resolve(string $timezone): TimezoneInfo
    {
        self::$timezoneIdentifiers ??= array_flip(DateTimeZone::listIdentifiers());

        if (isset(self::$timezoneIdentifiers[$timezone])) {
            return new TimezoneInfo($timezone);
        }

        $allAliases = self::$runtimeAliases + self::$aliases;
        if (isset($allAliases[$timezone])) {
            return new TimezoneInfo($allAliases[$timezone], $timezone);
        }

        self::$timezoneAbbreviations ??= DateTimeZone::listAbbreviations();
        $lower = strtolower($timezone);
        if (isset(self::$timezoneAbbreviations[$lower])) {
            $first = reset(self::$timezoneAbbreviations[$lower]);
            return new TimezoneInfo($first['timezone_id'], $timezone);
        }

        return new TimezoneInfo($timezone);
    }

    public static function toIana(string $timezone): string
    {
        return self::resolve($timezone)->getIanaName();
    }

    public static function isAlias(string $timezone): bool
    {
        return self::resolve($timezone)->isAlias();
    }

    /**
     * @param array<string, string> $aliases Map of alias => IANA name
     */
    public static function addAliases(array $aliases): void
    {
        self::$runtimeAliases = array_merge(self::$runtimeAliases, $aliases);
    }

    /**
     * @return array<string, string> Combined built-in and runtime aliases
     */
    public static function getAliases(): array
    {
        return self::$runtimeAliases + self::$aliases;
    }

    public static function resetRuntimeAliases(): void
    {
        self::$runtimeAliases = [];
        self::$timezoneIdentifiers = null;
        self::$timezoneAbbreviations = null;
    }
}
