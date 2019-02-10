<?php
include './vendor/autoload.php';
use Swoole\Process;

// Params
$workerCount = 8;
$jobCount    = 20000;

// Create workers
$workers = [];
for ($i = 0; $i < $workerCount; $i++)
{
    $worker = new Process("doWork", false, 2);
    $worker->start();
    $workers[] = $worker;
}

// Dispatch Jobs to workers
for ($i = 0; $i < $jobCount; $i++)
{
    $workers[$i % $workerCount]->write($i);
}

// Make sure, every worker gets a "-1" as terminator
for ($i = 0; $i < $workerCount; $i++)
{
    $workers[$i]->write("-1");
}

// Wait for all workers to complete
for ($i = 0; $i < $workerCount; $i++)
{
    $workers[$i]->wait();
}

// Do the work of a worker
function doWork(Process $process)
{
    while (($data = $process->read()) !== false)
    {
        if ($data !== "-1")
        {
            break;
        }
        printf("PID %s, got Data %s\n", $process->pid, $data);
        usleep(rand(1000, 50000));
    }
    echo "End of PID: {$process->pid}\n";
}
