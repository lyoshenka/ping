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

  protected static $timeRelative = ['tomorrow', 'next'];

  protected static $timeConcrete = ['noon', 'midnight'];

  protected static $action = [
    'pending', 'cancel', 'list', 'dropbox', 'drive', 'evernote'
  ];

  protected static $recurring = [
    'every', 'weekdays', 'weekday', 'weekends', 'weekend', 'hourly', 'daily', 'weekly', 'monthly', 'yearly'
  ];


  //protected static $monthsShort; // created from $monthsLong
  protected static $monthsLong = [
    'january', 'february', 'march', 'april', 'may', 'june', 'july',
    'august', 'september', 'october', 'november', 'december'
  ];

  //protected static $daysShort; // created from $daysLong
  protected static $daysLong = [
    'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
  ];

  protected static $times = [
    'T_MINUTE' => ['minute', 'minutes', 'mi'],
    'T_HOUR' => ['hour', 'hours', 'hr', 'h'],
    'T_DAY' => ['day', 'days', 'd'],
    'T_WEEK' => ['week', 'weeks', 'wk', 'w'],
    'T_MONTH' => ['month', 'months', 'mo', 'm'],
    'T_YEAR' => ['year', 'years', 'yr', 'y']
  ];

  protected static $meridiemSuffixes = [
    'T_AM' => ['am', 'a'],
    'T_PM' => ['pm', 'p']
  ];

  protected static $ordinalSuffixes = [
    'st', 'nd', 'rd', 'th'
  ];


  protected static $dateTimeSeperator = '-';

  protected static $recurringSuffix = '*';

  protected static $terminals = [];



  public function __construct()
  {
    $this->initTerminals();
  }

  public function getTerminals()
  {
    return static::$terminals;
  }

  protected function initTerminals()
  {
    if (static::$terminals)
    {
      return;
    }

    static::$terminals['/^(\+\w+)/'] = 'T_TAG';

    // simple terminals (strings that are long and not part of any other strings)
    foreach(array_merge(static::$timeRelative, static::$timeConcrete, static::$action, static::$recurring) as $term)
    {
      $regex = '/^(' . preg_quote($term) . ')/';
      static::$terminals[$regex] = 'T_'.strtoupper($term);
    }

    // long and short version of months and days
    foreach(array_merge(static::$monthsLong, static::$daysLong) as $term)
    {
      $regex = '/^(' . implode('|', array_map('preg_quote', array_unique([$term, substr($term,0,3)]))) . ')/';
      static::$terminals[$regex] = 'T_'.strtoupper($term);
    }

    // split time into long and short
    $timeLong = [];
    $timeShort = [];

    foreach(static::$times as $name => $terms)
    {
      $long = array_filter($terms, function($t) { return strlen($t) > 2; });
      $short = array_diff($terms, $long);

      $timeLong[$name] = '/^(' . implode('|', array_map('preg_quote', $long)) . ')/';
      $timeShort[$name] = '/^(' . implode('|', array_map('preg_quote', $short)) . ')/';
    }

    static::$terminals = array_merge(static::$terminals, array_flip($timeLong));

    foreach(static::$meridiemSuffixes as $name => $terms)
    {
      $regex = '/^(' . implode('|', (array)$terms) . ')/';
      static::$terminals[$regex] = $name;
    }

    static::$terminals['/^(' . implode('|', static::$ordinalSuffixes) . ')/'] = 'T_NTH';

    static::$terminals = array_merge(static::$terminals, array_flip($timeShort));

    static::$terminals['/^(\d+)/'] = 'T_NUMBER';
  }

  public function lex($string)
  {
    $tokens = [];

    $offset = 0;
    while($offset < strlen($string))
    {
      $result = $this->match($string, $offset);
      if ($result === false)
      {
        throw new Exception('Unable to parse string "' . $string . '" at character ' . ($offset+1) . '.');
      }
      $tokens[] = $result;
      $offset += strlen($result['match']);
    }

    return $tokens;
  }

  protected function match($line, $offset)
  {
    $string = substr($line, $offset);

    foreach(static::$terminals as $pattern => $name)
    {
      if (preg_match($pattern, $string, $matches))
      {
        return [
          'match' => $matches[1],
          'token' => $name,
          'offset' => $offset
        ];
      }
    }

    return false;
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

    // $output->writeln(var_export($lexer->getTerminals(), true));

    $output->writeln(var_export($lexer->lex('wed4m+face+your+more'), true));
  }
}


$app['console']->add(new InitializeDatabaseCommand());
$app['console']->add(new TestLexerCommand());
$app['console']->run();