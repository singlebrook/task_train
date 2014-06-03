<?php

namespace Tasktrain;

class Tasks {
  private $tasks;
  private $maxtasks = 100;
  private $scriptdir;

  public function __construct() {
    $this->tasks = array();
  }

  public function addTask($name, $params) {
    // Check if duplicate pending task exists (skip current).
    if ($this->findTask($name, $params)) {
      return FALSE;
    }

    $new_task = new Task($name, $params);
    $this->tasks[] = $new_task;

    // Keep the list of tasks shorter than the max allowed.
    if (count($this->tasks) > $this->maxtasks) {
      array_shift($this->tasks);
    }

    // TODO: Serialize $this->tasks and store in a temp file. $this->tasks could be reconstitued from this file on script restart.

    return $new_task;
  }

  public function findTask($name, $params, $status = 'pending') {
    $found_tasks = array();

    foreach ($this->tasks as $task) {
      if ($task->getName() == $name && $task->getParams() == $params && $task->getStatus() == $status) {
        $found_tasks[] = $task;
      }
    }

    return $found_tasks;
  }

  public function removeTask($id) {
    foreach ($this->tasks as $key => $task) {
      if ($task->getId() === $id) {
        unset($this->tasks[$key]);
        return TRUE;
      }
    }

    return FALSE;
  }

  public function getAll() {
    return $this->tasks;
  }

  public function getOne($id) {
    foreach ($this->tasks as $task) {
      if ($task->getId() == $id) {
        return $task;
      }
    }

    return FALSE;
  }

  public function count($status) {
    $count = 0;
    $statuses = explode(',', $status);

    foreach ($this->tasks as $task) {
      if (in_array($task->getStatus(), $statuses)) {
        $count++;
      }
    }

    return $count;
  }

  public function next($status) {
    $statuses = explode(',', $status);

    foreach ($this->tasks as $task) {
      if (in_array($task->getStatus(), $statuses)) {
        return $task;
      }
    }
  }

  public function __toString() {
    $output = '';

    foreach ($this->tasks as $task) {
      $output .= $task . PHP_EOL;
    }

    return $output;
  }
}
