<?php
require "./vendor/autoload.php";

use Swoole\Process;

// Workers running parallel
const WORKER_COUNT = 128;

$workers = [];
$tasks = [];

// Generate some dummy tasks
for($i = 0; $i < 1024; $i++) {
    $tasks[] = sha1(rand() . $i);
}

// Create the workers and launch the threads
for ($i = 0; $i < WORKER_COUNT; $i++) {
    $workers[$i] = new Process('worker', 0, 2);
    $workers[$i]->start();
}

$i = 0;
$rounds = 0;

/*
round = 0 send a task to every worker
round > 0 wait for completion message and send new task

To receive the result of the worker,
send data back to parent process with write inside worker
and read it back here.

For simplification only '0' and '1' is used.
*/
while ($task = array_pop($tasks)) {
    if($rounds) $success = $workers[$i]->read(); // '0' fail, '1' success
    $workers[$i]->write($task);

    if (++$i == WORKER_COUNT) {
        $i = 0;
        $rounds++;
    }
    printf("\r\e[KRound %d, Queue %d", $rounds, count($tasks));
}
echo PHP_EOL;

// Send '-1' to threads to end themselves when work done
for ($i = 0; $i < WORKER_COUNT; $i++) {
    $workers[$i]->write('-1');
}

echo 'Waiting for workers to complete', PHP_EOL;
for ($i = 0; $i < WORKER_COUNT; $i++) {
    $workers[$i]->wait();
}

function worker(Process $process)
{
    while (($message = $process->read()) !== false) {
        if ($message == '-1') {
            return;
        }

        // Do something with the data from $message
        $base = base64_encode($message) . PHP_EOL;
        unset($base);

        // Not needed, just used for simulating hard work
        sleep(rand(1,3));

        // Send a '0' or '1' back to the parent process to return success or failure
        $process->write('1');
    }
}
