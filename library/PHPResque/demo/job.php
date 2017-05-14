<?php
class PHP_Job
{
	public function perform()
	{
//        fwrite(STDOUT, 'Start job! -> ');
//		sleep(1);
//		fwrite(STDOUT, 'Job ended!' . PHP_EOL);
        sleep(5);

        $p = $this->args['name'];

        echo 'ttime '.$p;

        echo "string";
	}
}