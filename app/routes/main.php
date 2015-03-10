<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;



function parseTime($time)
{
  $years = $months = $weeks = $days = $hours = $minutes = 0;

  if (substr($time, '+') !== false)
  {
    // stuff after the plus is tags
    // jan1+tag@post.grin.io
  }

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

  // snooze@ -- snooze the reminder some amount of time? is this best done via api? include small links in footer?



  if (preg_match('/^(\d+)(\w+)$/', strtolower(trim($time)), $matches))
  {
    switch($matches[2])
    {
      case 'y':
      case 'yr':
      case 'yrs':
      case 'year':
      case 'years':
        $years = $matches[1];
        break;

      //case 'm': // ambiguous?
      case 'mo':
      case 'month':
      case 'months':
        $months = $matches[1];
        break;

      case 'w':
      case 'wk':
      case 'wks':
      case 'week':
      case 'weeks':
        $weeks = $matches[1];
        break;

      case 'd':
      case 'day':
      case 'days':
        $days = $matches[1];
        break;

      case 'h':
      case 'hr':
      case 'hrs':
      case 'hour':
      case 'hours':
        $hours = $matches[1];
        break;

      case 'mi':
      case 'min':
      case 'mins':
      case 'minute':
      case 'minutes':
        $minutes = $matches[1];
        break;

      default:
        // did not understand
    }
  }
  else
  {
    return null;
  }

  // return [interval, repetition, tags]

  return new DateInterval(sprintf('P%dY%dM%dDT%dH%dM',
    $years, $months, $days + (7*$weeks), $hours, $minutes
  ));
}

initMainRoutes($app);
function initMainRoutes($app) {
  $routes = $app['controllers_factory'];

  $routes->match('/', function(Request $request) use($app) {
    return '<h1>PING</h1>';
  })
  ->method('GET|POST|HEAD')
  ->bind('home');

  $routes->match('/mailgun_webhook', function(Request $request) use($app) {
    if ($request->getMethod() == 'HEAD')
    {
      return new Response('ok');
    }

    $app->log('Got an email from mailgun');
    $app->log('Subject is ' . $request->get('subject'));

    $validRequest = hash_hmac('sha256', $request->get('timestamp') . $request->get('token'), $app['mailgun_api_key']) === $request->get('signature');
    if (!$validRequest)
    {
      return $app->abort(403, "Invalid signature");
    }

    $app->log('Signature is valid');

    $recipient = $request->get('recipient');
    $time = substr($recipient, 0, strpos($recipient, '@'));
    $parsed = parseTime($time);

    if ($time === null)
    {
      $app->error('Could not parse "' . $time . '"');
    }

    $app->log('Recipient is ' . $recipient);

    $date = new DateTime();
    $date->add($parsed);

    $app['pdo']->execute('INSERT INTO ping (frequency, next_at, name, message, created_at) VALUES (?,?,?,?,?)',[
      $time, $date->format(DATE_RFC3339), trim($request->get('subject')), trim($request->get('body-plain')), date(DATE_RFC3339)
    ]);

    return new Response('ok');
  })
  ->method('POST|HEAD');



  $app->mount('/', $routes);
}
