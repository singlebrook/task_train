<?php
/**
 * The wrapper script that runs one or more scripts for a given task name.
 * 
 * Expects at least one option: --name=TASK_NAME, which is always automatically
 * added to the task.
 */

$args = getopt('', array(
  'name:',
));

if (!isset($args['name'])) {
  print 'Missing name option.' . PHP_EOL;
  exit(1);
}

if (!file_exists(__DIR__ . '/scripts.d/' . $args['name'])) {
  print 'Missing scripts for this task name.' . PHP_EOL;
  exit(2);
}

$scripts = new GlobIterator(__DIR__ . '/scripts.d/' . $args['name'] . '/*');

foreach ($scripts as $script) {
  if ($script->isExecutable()) {

    // Use the same PHP binary for php scripts.
    if ($script->getExtension() === 'php') {
      $script = PHP_BINARY . ' ' . $script;
    }

    // Add the arguments to the script. They were run through escapeshellarg()
    // already, so should be safe.
    foreach ($argv as $arg_num => $arg) {
      // Skip the first argument, which is this script name.
      if ($arg_num > 0) {
        $script .= ' ' . $arg;
      }
    }

    print $script . PHP_EOL;
    passthru($script, $exitcode);
    print 'exit code: ' . $exitcode . PHP_EOL . PHP_EOL;

    // If any of the included scripts fails. Stop and exit. This will fail the
    // task.
    if ($exitcode !== 0) {
      exit($exitcode);
    }
  }
}
