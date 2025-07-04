<!--Beginning of nav-->
<nav id="menu">
    <div id='cssmenu'>
        <ul>
            <?php
            $menu=array();
            $menu['Home']=array('href'=>'/','icon_class'=>'fa fa-home');


            //stocks
            $menu['Loans']=array('href'=>'#','class'=>'has-sub','icon_class'=>'fa fa-cubes');
            $menu['Loans']['submenu']['Loans']=array('href'=>'/loans');
            $menu['Loans']['submenu']['New Loans']=array('href'=>'/new_loans');
            $menu['Loans']['submenu']['Overdue Loans']=array('href'=>'/overdue_loans');



            $menu['Loan_request']=array('href'=>'#','class'=>'has-sub');
            $menu['Loan_request']['submenu']['All Loan_requests']=array('href'=>'/loan_request');
            $menu['Loan_request']['submenu']['New Loan Requests']=array('href'=>'/new_loan_request');


            $menu['Collections']=array('href'=>'#','class'=>'has-sub');
            $menu['Collections']['submenu']['All Loan_requests']=array('href'=>'/loan_request');
            $menu['Collections']['submenu']['New Loan Requests']=array('href'=>'/new_loan_request');


            $menu['Disbursment']=array('href'=>'#','class'=>'has-sub');
            $menu['Disbursment']['submenu']['All Loan_requests']=array('href'=>'/loan_request');
            $menu['Disbursment']['submenu']['New Loan Requests']=array('href'=>'/new_loan_request');




            $menu['Admin Menu']=array('href'=>'#','class'=>'has-sub');
            $menu['Admin Menu']['submenu']['Users']=array('href'=>'/users');
            $menu['Admin Menu']['submenu']['User Roles']=array('href'=>'/roles');
           // $menu['Admin Menu']['submenu']['Transactions']=array('href'=>'/transactions');
           //$menu['Admin Menu']['submenu']['Staffs']=array('href'=>'/staffs');

            $menu['Admin Menu']['submenu']['Audit Log']=array('href'=>'#','class'=>'has-sub');
            $menu['Admin Menu']['submenu']['Audit Log']['submenu']['User Activity Log']=array('href'=>'/activity_log');
            $menu['Admin Menu']['submenu']['Audit Log']['submenu']['Edited Data Log']=array('href'=>'/edited_data_log');

            $menu['My Account']=array('href'=>'#','class'=>'has-sub','icon_class'=>'fa fa-user','id'=>'my_account');
            $menu['My Account']['submenu']["Logged in as {$_SESSION['user_data']['first_name']}"]=array('href'=>'#');
            $menu['My Account']['submenu']['Change Password']=array('href'=>'/users/change_password','class'=>'open_modal');
            //$menu['My Account']['submenu']['Reset Password']=array('href'=>'/users/reset_password','class'=>'open_modal');
            $menu['My Account']['submenu']['Logout']=array('href'=>'/logout');
            echo generate_menu($menu);
            ?>
        </ul>
    </div>
</nav>
<!--End of nav-->
