<?php
include './vendor/autoload.php';
use Swoole\Process;

// Params
const WORKER_COUNT = 8;
const JOB_COUNT    = 20000;

// Create workers
$workers = [];
for ($i = 0; $i < WORKER_COUNT; $i++)
{
    $worker = new Process('worker', false, 2);
    $worker->start();
    $workers[] = $worker;
}

// Dispatch Jobs to workers
for ($i = 0; $i < JOB_COUNT; $i++)
{
    $workers[$i % WORKER_COUNT]->write($i);
}

// Make sure, every worker gets a "-1" as terminator
for ($i = 0; $i < WORKER_COUNT; $i++)
{
    $workers[$i]->write('-1');
}

// Wait for all workers to complete
for ($i = 0; $i < WORKER_COUNT; $i++)
{
    $workers[$i]->wait();
}

// Do the work of a worker
function worker(Process $process)
{
    while (($data = $process->read()) !== false)
    {
        if ($data !== '-1')
        {
            break;
        }
        printf("PID %s, got Data %s\n", $process->pid, $data);
        usleep(rand(1000, 50000));
    }
    echo "End of PID: {$process->pid}\n";
}
