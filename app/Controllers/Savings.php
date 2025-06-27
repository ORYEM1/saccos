<?php
namespace App\Controllers;

class Savings extends RestrictedBaseController
{
    private string $controller;

    public function __construct()
    {
        $this->controller = strtolower((new \ReflectionClass($this))->getShortName());

    }
    public function index()
    {
        if (user_has_access($this->controller, __FUNCTION__)) {
            $table = 'savings';
            $_SESSION["search_{$table}"] = array();
            $_SESSION["search_{$table}_where_in"] = array();
            $_SESSION["search_{$table}_like"] = array();
            if ($this->request->getPost('search')) {
                $params = array();
                $params['table_name'] = $table;
                $params['where'] = $where ?? array();
                $params['where_in'] = $where_in ?? array();
                $params['where_search_fields'] = array('id' => 'id', 'username' => 'username');
                $params['like_search_fields'] = array('date_created' => 'users.date_time_created', 'phone_number' => 'users.phone_number');
                $params['where_in_search_fields'] = array('status' => 'users.status', 'role' => 'users.role', 'gender' => 'users.gender');
                $search_range = array();
                $search_range['from_date'] = array('column' => 'date_time_created', 'operator' => '>=');
                $search_range['to_date'] = array('column' => 'date_time_created', 'operator' => '<=');
                $params['search_range'] = $search_range;
                $this->set_search_data($params);
            }
            $vars['content_view'] = 'data_table';
            $vars['title'] = 'Savings';
            $vars['page_heading'] = 'Savings';


            //Data header
            //============================================================================================
            $data_header[] = array('name' => 'ID', 'sortable' => true, 'db_col_name' => 'loan_id');
            $data_header[] = array('name' => 'FIRST NAME', 'sortable' => true, 'db_col_name' => 'first_name');
            $data_header[] = array('name' => 'LAST NAME', 'sortable' => true, 'db_col_name' => 'last_name');
            $data_header[] = array('name' => 'USERNAME', 'sortable' => true, 'db_col_name' => 'username');
            $data_header[] = array('name' => 'PHONE NUMBER', 'sortable' => false, 'db_col_name' => 'phone_number');
            $data_header[] = array('name' => 'PAYMENT METHOD', 'sortable' => false, 'db_col_name' => 'payment_method');
            $data_header[] = array('name' => 'AMOUNT DEPOSITED', 'sortable' => false, 'db_col_name' => 'amount_deposited');
            $data_header[] = array('name' => 'TOTAL BALANCE', 'sortable' => false, 'db_col_name' => 'total_balance');
            $data_header[] = array('name' => 'STATUS', 'sortable' => false, 'db_col_name' => 'status');
            $data_header[] = array('name' => 'DATE', 'sortable' => false, 'db_col_name' => 'date');
            $data_header[] = array('name' => 'CREATED BY', 'sortable' => true, 'db_col_name' => 'created_by');
            $data_header[] = array('name' => '', 'class' => 'icon_col', 'sortable' => false);
            $data_header[] = array('name' => '', 'class' => 'icon_col', 'sortable' => false);
            $data_header[] = array('name' => '', 'class' => 'icon_col', 'sortable' => false);
            $data_header[] = array('name' => '<input type="checkbox" class="check_all_boxes" data-target_class="select_record" title="Select All" />', 'class' => 'icon_col', 'sortable' => false);
            $vars['data_header'] = $data_header;

            //Data Footer
            //============================================================================================
            $data_footer[] = get_new_link_button(array('url' => '/users/new_savings', 'label' => 'New Savings', 'icon' => 'fa fa-plus'));
            $vars['data_footer'] = $data_footer;

            //Data tables options
            //============================================================================================
            $dt_params = array('ajax' => '/data_tables/get_data/get_savings', 'bFilter' => true, 'order_columns' => array('ID' => 'desc'));
            $vars['data_tables_config'] = get_dt_config($data_header, $dt_params);

        } else {
            $vars['content_view'] = 'unauthorized';
            $vars['title'] = '401 Unauthorized';
        }
        return view('page', $vars);
    }


    public function new_savings($load_type='')
    {
        if($this->request->getPost('submit'))
        {
            unset($_POST['submit']);
            if(!user_has_access($this->controller,__FUNCTION__))
            {
                exit(json_encode(array('status' => 'error', 'message' => "You don't have access to this page")));

            }
            $validation=\Config\Services::validation();
            $validation_rules=array(
                'username'=>'required',
                'amount_deposited'=>'required',



            );
            $validation->setRules($validation_rules);
            if($validation->withRequest($this->request)->run())
            {
                $username  = $this->request->getPost('username');
                $amount_deposited = $this->request->getPost('amount_deposited');

                $user = $this->base_model->get_data(['table' => 'savings', 'where' => ['username' => $username]],true);
            }
            if(!$user)
            {
                return json_encode(array('status' => 'error', 'message' => "Invalid Username"));
            }

                $save_data = [
                    'username' => $user->username,
                    'amount_deposited' => $amount_deposited,
                    'date_created' => date('Y-m-d H:i:s'),
                ];

            $this->base_model->insert_data( 'savings',$save_data);

            return json_encode(array('status' => 'savings added successfully'));


        }
        else
        {
            if(user_has_access($this->controller,__FUNCTION__))
            {
                $config=array();
                $config['username'] = [
                    'field_type' => 'text_field',
                    'label' => 'Username',
                    'type' => 'text',
                    'required' => 'required',
                    'value' => $_POST['username'] ?? ''
                ];

                $config['amount_deposited'] = [
                    'field_type' => 'text_field',
                    'label' => 'Amount Deposited',
                    'type' => 'number',
                    'required' => 'required',
                    'value' => $_POST['amount_deposited'] ?? ''
                ];

                $vars['form_data'] = get_form_data($config);
                $vars['form_title'] = 'Add Savings';
                $vars['submit_url'] = "/savings/new_savings";
                $vars['content_view'] = 'form';
                $vars['title'] = 'New Savings';

                return view($vars['content_view'], $vars);
            }
            else
            {
                $vars['content_view'] = 'unauthorized';
                $vars['title'] = '401 Unauthorized';
            }
            return view($vars['content_view'], $vars);
        }


    }



}