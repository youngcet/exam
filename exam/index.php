<?php
    session_start();

    include 'autoloader.php';

    class Index
    {
        public function __construct()
        {
            $this->_data = [];
            $this->_data['{code_sample}'] = file_get_contents ('samplecode.html');
            $this->_data['{expired.alert}'] = '';
        }

        public function render()
        {
            if (isset ($_GET['expired']))
            {
                file_put_contents ('expired.txt', 'done');
                header ('Location: expired.html');
                die();
            }

            if (isset ($_POST['done']))
            {
                file_put_contents ('done.txt', print_r($_POST, true)); // dump the results in this file
                header ('Location: done.html');
                die();
            }

            if (file_exists ('done.txt'))
            {
                header ('Location: done.html');
                die();
            }

            if (file_exists ('expired.txt'))
            {
                $this->_data['{expired.alert}'] = 'alertify.alert("Test has expired!", 
                "You do no longer have access to this test because it has expired. For any questions send an email to test@test.com.", 
                function(){window.location.href = "expired.html"; });';
            }

            return App\Web\Page\SubstTemplate::GetSubstitutedString (file_get_contents ('pages/index.html'), $this->_data);
        }
    }

    $indexpage = new Index();
    echo $indexpage->render();
?>