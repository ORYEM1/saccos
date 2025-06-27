<?php
namespace App\Controllers;

class Loan_request extends RestrictedBaseController
{
    private string $controller;

    public function __construct()
    {
        $this->controller = strtolower((new \ReflectionClass($this))->getShortName());

    }
    public function index()
    {
        if (user_has_access($this->controller, __FUNCTION__)) {
            $table = 'loan_requests';
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
            $vars['title'] = 'Loan Requests';
            $vars['page_heading'] = 'Loan Requests';


            //Data header
            //============================================================================================
            $data_header[] = array('name' => 'ID', 'sortable' => true, 'db_col_name' => 'request_id');
            $data_header[] = array('name' => 'USERNAME', 'sortable' => true, 'db_col_name' => 'username');
            $data_header[] = array('name' => 'INTEREST RATE', 'sortable' => true, 'db_col_name' => 'interest_rate');
            $data_header[] = array('name' => 'LOAN TYPE', 'sortable' => false,'db_col_name' => 'loan_type');
            $data_header[] = array('name' => 'PHONE NUMBER', 'sortable' => false, 'db_col_name' => 'phone_number');
            $data_header[] = array('name' => 'AMOUNT REQUESTED', 'sortable' => false, 'db_col_name' => 'amount_requested');
            $data_header[] = array('name' => '', 'sortable' => false, 'db_col_name' => 'amount_approved');
            $data_header[] = array('name' => 'STATUS', 'sortable' => false, 'db_col_name' => 'status');
            $data_header[] = array('name' => 'DURATION', 'sortable' => false, 'db_col_name' => 'duration');
            $data_header[] = array('name' => 'APPLICATION DATE', 'sortable' => true, 'db_col_name' => 'gender');
            $data_header[] = array('name' => 'LIFE SPAN', 'sortable' => false, 'db_col_name' => 'life_span');
            $data_header[] = array('name' => 'LOAN OFFICER', 'sortable' => true, 'db_col_name' => 'date_time_created');
            $data_header[] = array('name' => 'QUARENTOR 1', 'sortable' => false, 'db_col_name' => 'quarentor1');
            $data_header[] = array('name' => 'QUARENTOR 2', 'sortable' => false, 'db_col_name' => 'quarentor2');
            $data_header[] = array('name' => 'CREATED BY', 'sortable' => true, 'db_col_name' => 'created_by');
            $data_header[] = array('name' => '', 'class' => 'icon_col', 'sortable' => false);
            $data_header[] = array('name' => '', 'class' => 'icon_col', 'sortable' => false);
            $data_header[] = array('name' => '', 'class' => 'icon_col', 'sortable' => false);
            $data_header[] = array('name' => '<input type="checkbox" class="check_all_boxes" data-target_class="select_record" title="Select All" />', 'class' => 'icon_col', 'sortable' => false);
            $vars['data_header'] = $data_header;

            //Data Footer
            //============================================================================================

            $data_footer[] = get_new_link_button(array('url' => '/loan_request/new_loan_request', 'label' => 'New Loan Request', 'icon' => 'fa fa-plus'));
            $vars['data_footer'] = $data_footer;

            //Data tables options
            //============================================================================================
            $dt_params = array('ajax' => '/data_tables/get_data/get_loan_requests', 'bFilter' => true, 'order_columns' => array('ID' => 'desc'));
            $vars['data_tables_config'] = get_dt_config($data_header, $dt_params);

        } else {
            $vars['content_view'] = 'unauthorized';
            $vars['title'] = '401 Unauthorized';
        }
        return view('page', $vars);
    }
    public function new_loan_request()
    {
        if (user_has_access($this->controller, __FUNCTION__))
        {
            $table = 'loan_requests';
            $_SESSION["search_{$table}"] = array();
            $_SESSION["search_{$table}_where_in"] = array();
            $_SESSION["search_{$table}_like"] = array();
            $_SESSION["search_{$table}_where_search_fields"] = array();
            $_SESSION["search_{$table}_like"] = array();



        }
    }
}