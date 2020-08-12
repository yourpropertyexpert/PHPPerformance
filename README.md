# PHPPerformance
A test suite to compare the performance of various ways of data in PHP.
For simulation purposes, it runs a "get a random number" 5000 times.
It gets that random number a lot of different ways. After each one, it outputs the timings (flusing as it goes)


# How to run

- Make sure you have docker and docker-compose installed
- clone this repo
- from the root directory of the repo run "docker-compose up"
- point your webbrowser at localhost
- In practice, ignore the first run, which may include some results from a point at which the docker service were still spinning up... so once you see the "done" message, hit "refresh" and use those instead.


# What methods are currently there

- A simple loop on a PHP page
- Class - Single method call that did the iteration in a loop
- Class - Loop that called the method each time
- Class - Single method call, that ran a loop calling class shared memcached each time
- Class - Single method call, that ran a loop calling class shared Redis each time
- Class - Single method call, that ran a loop calling a new SQL query each time
- Class - Single method call, that ran a loop calling class which uses Guzzle to call an API each time


# To Do (new algorithms)

- Calling a method that gets a number by making a Guzzle multi-call
- DB using prepared statements bound each time
- DB using prepared statements bound once
- Call local function in same namespace

# To Do (other)

- Some graphical display of the results
