<?php

namespace Tasktrain;

class Task {
  private $id;
  private $name;
  private $params;
  private $status;
  private $created;
  private $process;
  private $results;

  public function __construct($name, $params, $default_status = 'pending') {
    $process_args = '--name=' . escapeshellarg($name);
    foreach ($params as $key => $val) {
      $key = preg_replace('/[^a-z_]/', '_', $key);
      $process_args .= ' --' . $key . '="' . escapeshellarg($val) . '"';
    }

    date_default_timezone_set('America/New_York');
    $this->id = uniqid();
    $this->name = $name;
    $this->params = $params;
    $this->status = $default_status;
    $this->created = new \DateTime();
    $this->process = new \React\ChildProcess\Process(PHP_BINARY . ' process.php ' . $process_args);
    $this->results = NULL;
  }

  public function getId() {
    return $this->id;
  }

  public function getName() {
    return $this->name;
  }

  public function getParams() {
    return $this->params;
  }

  public function getStatus() {
    return $this->status;
  }

  public function getCreated($format = 'str') {
    if ($format == 'str') {
      return $this->created->format('r');
    }
    else {
      return $this->created;
    }
  }

  public function getProcess() {
    return $this->process->getCommand();
  }

  public function getAsArray() {
    return array(
      'id' => $this->id,
      'name' => $this->name,
      'params' => $this->params,
      'status' => $this->status,
      'created' => $this->created,
      'process' => $this->process->getCommand(),
      'results' => $this->results,
    );
  }

  public function run($loop) {
    $this->status = 'running';
    $this->results = '';

    $this->process->on('exit', function($exitCode, $termSignal) {
      if ($exitCode === 0) {
        $this->status = 'complete';
      }
      else {
        $this->status = 'failed';
      }
    });

    $this->process->start($loop);

    $this->process->stdout->on('data', function($output) {
      $this->results .= "$output";
    });
  }

  public function __toString() {
    $created = $this->created->format('r');
    $params = json_encode($this->params);
    $process = $this->process->getCommand();

    return <<<EOF
ID:      $this->id
NAME:    $this->name
PARAMS:  $params
STATUS:  $this->status
CREATED: $created
PROCESS: $process
RESULTS: $this->results

EOF;
  }
}
