# PHPPerformance
A test suite to compare the performance of various ways of data in PHP.
For simulation purposes, it runs a "get a random number" 500 times.
It gets that random number a lot of different ways. After each one, it outputs the timings (flushing as it goes)


# How to run

- Make sure you have docker and docker-compose installed
- clone this repo
- from the root directory of the repo run "docker-compose up"
- point your webbrowser at localhost
- In practice, ignore the first run, which may include some results from a point at which the docker service were still spinning up... so once you see the "done" message, hit "refresh" and use those instead.


# What methods are currently there

- A simple loop on a PHP page
- Run a local function (parameterised) that does the loop
- Run a local function (unparameterised - using constants to control number of iterations) that does the loop
- Class - Single method call that did the iteration in a loop
- Class - Loop that called the method each time
- Class - Single method call, that ran a loop calling class shared memcached each time
- Class - Single method call, that ran a loop calling class shared Redis each time
- Class - Single method call, that ran a loop calling a new MySQL query each time
- Class - Single method call, that made a single MySQL query then looped over the returned data
- Class - Single method call, that ran a loop calling a new SQLite query each time
- Class - Single method call, that ran a loop calling class which uses Guzzle to call an API each time


# To Do (new algorithms)

- Calling a method that gets a number by making a Guzzle multi-call
- DB using prepared statements bound each time
- DB using prepared statements bound once
