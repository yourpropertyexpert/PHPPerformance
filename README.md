# PHPPerformance
A test suite to compare the performance of various ways of data in PHP.
For simulation purposes, it runs a "get a random number" a configured number of times.
It gets that random number a lot of different ways. After each one, it outputs the timings (flushing as it goes)


# How to run

- Make sure you have docker and docker-compose installed
- clone this repo
- from the root directory of the repo run "docker-compose up"
- point your webbrowser at localhost
- In practice, ignore the first run, which may include some results from a point at which the docker service were still spinning up... so once you see the "done" message, hit "refresh" and use those instead.


# What "ways" are currently there

- Page: Simple loop
- Page: Local function doing the iteration internally
- Page: Local function called multiple times
- Class: Null function called once (to assess overhead of just calling)
- Class: Null function called n times (to assess overhead of just calling)
- Class: Single method call that did the iteration in the method
- Class: Call the method multiple times from a loop in the calling page
- External: Single method call, that ran a loop calling local memcached on a unix socket each time
- External: Single method call, that ran a loop calling a shared memcached over UDP each time
- External: Single method call, that ran a loop calling class shared Redis each time
- External: Single method call, n MySQL queries (not prepared statment)
- External: Single method call, n MySQL queries against same prepared statement
- External: Single method call, that ran one MySQL query then looped over the returned data
- External: Run a loop calling a new SQLite query each time
- External: Make a single SQLite query and then unpack the results
- External: Single method call, that ran a loop calling an API using cURL each time
- External: Single method call, that ran a loop calling an API using Guzzle each time

# To Do (new "ways")

- Calling a method that gets a number by making a Guzzle multi-call

# The "mothership"

When running a set of tests, the user has the option to "report the results back to the mothership."

The mothership is a webservice in the cloud. Reporting to it does not store any personally identifying information.

The mothership aggregates data about how the different "ways" have performed in given test runs. This is used to generate an index of relative performance at different sample sizes, which is viewable on the stats page in the frontend here.

The mothership also stores a hash of the "ways" array, so that we can distinguish between different versions of the PHPPerformance software, and ensure we're comparing like with like.
