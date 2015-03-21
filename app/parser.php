<?php

  // formats
  // jan1@
  // monday@
  // 4weeks@
  // 2d3m@
  // 8pm@
  // 2000@ - military time
  // monday9am@
  // jan1-9am@

  // Any reminder can be made recurring by either adding the word every to the beggining of the reminder OR adding an asterisk (*) to the end of the reminder.
  // everyjan1@ -- day of year
  // jan1*@
  // everymon9am@ -- day of week
  // every1st@ -- day of month
  // daily-8:30am@ -- also weekly, monthly, yearly

  // tags
  // +taxes@
  // tuesday12pm+meeting@
  // jan1+newyear+resolution@
  // jan1-newyear,resolution@

  // who to email the reminder to
  // To:  Schedule an email reminder for yourself, or Forward an email to reappear on a future date.
  // Cc:  Schedule a reminder for everyone included in the email.
  // Bcc: Schedule a reminder within an email that only you will see and receive.


  // pending@ -- get a list of all your reminders
  // cancel@ -- cancel a reminder (only works if you reply to an email you sent a reminder from)
  // drive@, dropbox@ -- save attachment to dropbox or google drive

  // ping@ -- respond with PONG

  // snooze@ -- snooze the reminder some amount of time? is this best done via api? include small links in footer?



class Parser
{
  protected static $dayAlias = 'tomorrow';
  protected static $timeAlias = ['noon', 'midnight'];
  // LONGEST TO SHORTEST
  protected static $intervals = [
    'minutes', 'minute', 'mi',
    'hours', 'hour', 'hr', 'h',
    'days', 'day', 'd',
    'weeks', 'week', 'wk', 'w',
    'months', 'month', 'mo', 'm',
    'years', 'year', 'yr', 'y'
  ];
  protected static $meridiemSuffixes = ['am', 'pm', 'a', 'p'];
  protected static $dateTimeSeperator = '-';
  protected static $recurringSuffix = '*';
  protected function initTerminals()
  {
    static::$terminals['/^(' . static::$dayAlias . ')/'] = 'T_DAY_ALIAS';
    static::$terminals['/^(' . implode('|', static::$timeAlias) . ')/'] = 'T_TIME_ALIAS';

    static::$terminals["/^('\d\d)/"] = 'T_YEAR_NUMBER';

    $long = array_filter(static::$intervals, function($i) { return strlen($i) > 2; });
    $short = array_diff(static::$intervals, $long);

    static::$terminals['/^(' . implode('|', $long) . ')/'] = 'T_INTERVAL_LONG';

    static::$terminals['/^(\d+(' . implode('|', static::$meridiemSuffixes) . '))/'] = 'T_TIME_WITH_MERIDIEM';

    static::$terminals['/^(' . implode('|', $short) . ')/'] = 'T_INTERVAL_SHORT';

    static::$terminals['/^(' . preg_quote(static::$dateTimeSeperator) . ')/'] = 'T_DATE_TIME_SEPERATOR';
    static::$terminals['/^(' . preg_quote(static::$recurringSuffix) . ')/'] = 'T_RECURRING_SUFFIX';
  }



  public function parse($string)
  {
    $month_long = '(?P<month_long>january|february|march|april|may|june|july|august|september|october|november|december)';
    $month_short = '(?P<month_short>jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)';
    $month = '(?P<month>' . $month_long . '|' . $month_short . ')';

    $meridiem = '(?P<meridiem>am?|pm?)';
    $time_military = '(?P<time_military>(?:2[0-3]|[01][0-9])[0-5][0-9])';
    $time_standard = '';
    $time = '(?P<time>' . $time_standard . '|' . $time_military . ')';

    $date_number = '(?P<date_number>(?:3[012]|[12]?[0-9]))';
    $date_ordinal_suffix = '(?P<date_ordinal_suffix>st|nd|rd|th)';
    $date = '(?P<date>' . $date_number . $date_ordinal_suffix . '?)';

    $day_long = '(?P<day_long>sunday|monday|tuesday|wednesday|thursday|friday|saturday|sunday)';
    $day_short = '(?P<day_short>sun|mon|tue|wed|thu|fri|sat|sun)';
    $day = '(?P<day>' . $day_long . '|' . $day_short . ')';

    $month_date = '(?P<month_date>' . $month . $date . ')';
    $month_date_or_day = '(?P<month_date_or_day>' . $month_date . '|' . $day . ')';

    $next = '(?P<next>next)';
    $every = '(?P<every>every)';
    $every_or_next = '(?P<every_or_next>' . $every . '|' . $next . ')';

    $recurrence = '(?P<recurrence>hourly|daily|weekly|monthly|yearly|weekdays?|weekends?)';

    $reminder = '(?P<reminder>' . $recurrence . '|(?:' . $every_or_next . '?' . $month_date_or_day . '))';

    $action = '(?P<action>pending|cancel|list|dropbox|drive|evernote)';
    $action_or_reminder = '(?P<action_or_reminder>' . $action . '|' . $reminder . ')';

    $tag = '(?P<tag>\+[a-z]+)';
    $tags = '(?P<tags>' . $tag . '*)';

    $regex = '#^' . $action_or_reminder . $tags . '$#';

    preg_match($regex, $string, $matches);

    foreach ($matches as $k => $v)
    {
      if (is_int($k))
      {
        unset($matches[$k]);
      }
    }

    return $matches;
  }

  public function getNextAndRepeat($data)
  {
    if ($data['recurrence'] || $data['every'])
    {

    }
    else
    {
      $repeat = null;
    }

    $day = $data['day'];
    $date = $data['date_number'];
    $month = $data['month'];



    if ($data['next'])
    {

    }


    return [$next, $repeat];
  }

  public function process($when, $app, $name, $message, $email)
  {
    list($next,$repeat) = getNextAndRepeat($this->parse($when));

    if ($next === null)
    {
      $app->error('Could not parse "' . $when . '"');
    }

    $app->log('Recipient is ' . $recipient);

    $app['pdo']->execute('INSERT INTO ping (when, next_at, repeat, name, message, created_at) VALUES (?,?,?,?,?,?,?)',[
      $when, $next ? $next->format(DATE_RFC3339) : null, $repeat, $name, $message, $email, date(DATE_RFC3339)
    ]);

    return [$next, $repeat];
  }
}