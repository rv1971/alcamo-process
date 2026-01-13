# Usage example

~~~
use alcamo\process\Process;

$process = new Process('echo Hello, world!');

$hello = $process->fgets();
~~~

Now $hello contains the string "Hello, world!" (plus the
platform-dependent line break).

The usual PHP functions writing to reading from streams can be used as
magic methods on Process objects to write to and read from the child
process's standard input/output.

A ProcessFactory class is provided to facilitate creating processes
which all use the same underlying program. For instance:

~~~
use alcamo\process\ProcessFactory;

$gitFactory = new ProcessFactory('/home/alice', '/usr/bin/git');

$configProcess = $gitFactory->create('config -l');

$configData = $config->stream_get_contents();

~~~
