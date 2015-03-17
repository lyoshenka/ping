#!/usr/bin/env php
<?php

set_time_limit(0);

$appDir = __DIR__.'/../app';

require_once "$appDir/bootstrap.php";

$app->register(new Knp\Provider\ConsoleServiceProvider(), [
  'console.name' => 'ping',
  'console.version' => '1',
  'console.project_directory' => $appDir
]);


class InitializeDatabaseCommand extends \Knp\Command\Command {
  protected function configure() {
    $this
      ->setName("init-db")
      ->setDescription("Initialize the database")
    ;
  }

  protected function execute(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output) {
    $app = $this->getSilexApplication();
    $pdo = $app['pdo'];

    if (!file_exists($app['pdo.db']))
    {
      touch ($app['pdo.db']);
      chmod($app['pdo.db'], 0666);
      chmod(dirname($app['pdo.db']), 0777); // sqlite needs dir to be writable
    }

    //$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $output->writeln('creating `ping` table');
    $pdo->exec('DROP TABLE IF EXISTS ping');
    $pdo->exec("CREATE TABLE `ping` (
        `when` TEXT NOT NULL,
        `created_at` DATETIME NOT NULL,
        `next_at` DATETIME,
        `name` TEXT,
        `message` TEXT
      )"
    );
  }
}


class Lexer {

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

  public function parse($str)
  {

    $month_long = '(?P<month_long>january|february|march|april|may|june|july|august|september|october|november|december)';
    $month_short = '(?P<month_short>jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)';
    $month = '(?P<month>' . $month_long . '|' . $month_short . ')';

    $meridiem = '(?P<meridiem>am?|pm?)';
    $time_military = '(?P<time_military>[012][0-9][0-6][0-9])';
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

    preg_match($regex, $str, $matches);

    foreach ($matches as $k => $v)
    {
      if (is_int($k))
      {
        unset($matches[$k]);
      }
    }

    return $matches;
  }

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
}


class TestLexerCommand extends \Knp\Command\Command {
  protected function configure() {
    $this
      ->setName("test-lexer")
      ->setDescription("Test lexer")
    ;
  }

  protected function execute(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output) {
    $app = $this->getSilexApplication();

    $lexer = new Lexer();

    var_export($lexer->parse('nextmarch15st+face+your'));

    // $output->writeln(var_export($lexer->getTerminals(), true));

    // $output->writeln(var_export($lexer->lex('weekdaysnextwed4\'16mar34st+face+your+more4pm'), true));
  }
}


$app['console']->add(new InitializeDatabaseCommand());
$app['console']->add(new TestLexerCommand());
$app['console']->run();