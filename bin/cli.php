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
        `next_at` DATETIME,
        `repeat` INT,
        `name` TEXT,
        `message` TEXT,
        `email` TEXT,
        `created_at` DATETIME NOT NULL
      )"
    );
  }
}


class TestLexerCommand extends \Knp\Command\Command {
  protected function configure() {
    $this
      ->setName("test")
      ->setDescription("Test the parser")
    ;
  }

  protected function execute(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output) {
    $app = $this->getSilexApplication();

    var_export($app['parser']->process('1year', $app, 'Test', 'This is a test', 'fake@fake.fake'));

    // $output->writeln(var_export($lexer->getTerminals(), true));

    // $output->writeln(var_export($lexer->lex('weekdaysnextwed4\'16mar34st+face+your+more4pm'), true));
  }
}


$app['console']->add(new InitializeDatabaseCommand());
$app['console']->add(new TestLexerCommand());
$app['console']->run();