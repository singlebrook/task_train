<?php
/**
 * Creates, manages, and processes a queue of deployment tasks.
 */

require 'vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket);

$r = 0;
$tasks = new Tasktrain\Tasks;
$port = 1337;

$app = function($request, $response) use (&$r, $tasks, $port) {
  $r++;
  $method = $request->getMethod();
  $path = $request->getPath();
  $query = $request->getQuery();
  $headers = array('Content-Type' => 'text/plain', 'X-Req-Count' => $r);
  $format = isset($query['format']) && in_array($query['format'], array('text', 'json', 'html')) ? $query['format'] : 'html';
  $status = 200;

  // Super simple router.
  if ($path === '/') {
    $output = 'Task Manager is running!';
  }
  else if ($path === '/task' && $method == 'POST') {
    $valid_err = '';

    if (!isset($query['name']) || !preg_match('/^[a-z0-9_\-]{3,24}$/', $query['name'])) {
      $valid_err = 'Bad or missing name parameter.' . PHP_EOL;
    }

    if (!$valid_err) {
      $params = $query;
      unset($params['name']);
      $task = $tasks->addTask($query['name'], $params);
      $output = $task;
    } else {
      $status = 500;
      $output = $valid_err;
    }
  }
  else if ($path === '/tasks') {
    $headers['Content-Type'] = 'text/html';
    $output = '';
    $all_tasks = $tasks->getAll();
    rsort($all_tasks);

    $output .= '<p>' . $tasks->count('pending') . ' pending task(s).</p>';

    foreach ($all_tasks as $task) {
      $format = <<<EOL
<div class="%s">
<p>ID: <a href="http://127.0.0.1:$port/tasks/%s">%s</a><br />
Name: %s<br />
Params: %s<br />
Status: %s<br />
Date: %s</p>
</div>
EOL;
      $output .= sprintf($format, $task->getStatus(), $task->getId(), $task->getId(),
          $task->getName(), json_encode($task->getParams()), $task->getStatus(),
          $task->getCreated());
    }
  }
  else if (preg_match('~/tasks/([a-z\d]{13})~', $path, $m)) {
    $output = $tasks->getOne($m[1]);
  }
  else {
    $status = 404;
    $output = 'Not found';
  }

  $response->writeHead($status, $headers);

  if ($headers['Content-Type'] == 'text/html') {
    $css = 'div { padding: 0 .5em; } .complete { color: gray; } .running { background: yellow; } .failed { color: #8B0000; }';
    $output = sprintf('<html><head><title>Task Train</title><style>%s</style></head><body>%s</body></html>', $css, $output);
  }
  $response->end($output);
};

$http->on('request', $app);

$socket->listen($port);
echo "Server running at http://127.0.0.1:$port\n";

// Check for tasks to run every second.
// TODO: Consider using some sort of emitter if that would consume fewer resources.
$loop->addPeriodicTimer(1, function($timer) use ($tasks) {
  if ($tasks->count('running') === 0 && $tasks->count('pending') > 0) {
    $tasks->next('pending')->run($timer->getLoop());
  }
});

$loop->run();
