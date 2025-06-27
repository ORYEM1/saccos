<?php
namespace App\Controllers;

class Loans extends RestrictedBaseController
{
    private string $controller;

    public function __construct()
    {
        $this->controller = strtolower((new \ReflectionClass($this))->getShortName());

    }
    public function index()
    {
        if (user_has_access($this->controller, __FUNCTION__)) {
            $table = 'loans';
            $_SESSION["search_{$table}"] = array();
            $_SESSION["search_{$table}_where_in"] = array();
            $_SESSION["search_{$table}_like"] = array();
            if ($this->request->getPost('search')) {
                $params = array();
                $params['table_name'] = $table;
                $params['where'] = $where ?? array();
                $params['where_in'] = $where_in ?? array();
                $params['where_search_fields'] = array('loan_id' => 'loan_id', 'username' => 'username');
                $params['like_search_fields'] = array('date_created' => 'users.date_time_created', 'phone_number' => 'users.phone_number');
                $params['where_in_search_fields'] = array('status' => 'users.status', 'role' => 'users.role', 'gender' => 'users.gender');
                $search_range = array();
                $search_range['from_date'] = array('column' => 'date_time_created', 'operator' => '>=');
                $search_range['to_date'] = array('column' => 'date_time_created', 'operator' => '<=');
                $params['search_range'] = $search_range;
                $this->set_search_data($params);
            }
            $vars['content_view'] = 'data_table';
            $vars['title'] = 'Loans';
            $vars['page_heading'] = 'Loans';


            //Data header
            //============================================================================================
            $data_header[] = array('name' => 'ID', 'sortable' => true, 'db_col_name' => 'loan_id');
            $data_header[] = array('name' => 'USERNAME', 'sortable' => true, 'db_col_name' => 'username');
            $data_header[] = array('name' => 'INTEREST RATE', 'sortable' => true, 'db_col_name' => 'interest_rate');
            $data_header[] = array('name' => 'LOAN TYPE', 'sortable' => false,'db_col_name' => 'loan_type');
            $data_header[] = array('name' => 'PHONE NUMBER', 'sortable' => false, 'db_col_name' => 'phone_number');
            $data_header[] = array('name' => 'AMOUNT REQUESTED', 'sortable' => false, 'db_col_name' => 'amount_requested');
            $data_header[] = array('name' => 'AMOUNT APPROVED', 'sortable' => false, 'db_col_name' => 'amount_approved');
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

            /*$data_footer[] = get_new_link_button(array('url' => '/users/new_user', 'label' => 'New User', 'icon' => 'fa fa-plus'));
            $vars['data_footer'] = $data_footer;*/

            //Data tables options
            //============================================================================================
            $dt_params = array('ajax' => '/data_tables/get_data/get_loans', 'bFilter' => true, 'order_columns' => array('ID' => 'desc'));
            $vars['data_tables_config'] = get_dt_config($data_header, $dt_params);

        }
        else {
            $vars['content_view'] = 'unauthorized';
            $vars['title'] = '401 Unauthorized';
        }
        return view('page', $vars);
    }


    public function new_loan()
    {
        if ($this->request->getPost('submit')) {
            // Check access
            if (!user_has_access($this->controller, __FUNCTION__)) {
                return $this->response->setJSON(['status' => 0, 'message' => 'Access Denied']);
            }

            // Validation rules
            $validation = \Config\Services::validation();
            $rules = [
                'loan_code' => 'required',
                'borrower_name' => 'required',
                'borrower_phone' => 'required|numeric|min_length[10]|max_length[10]',
                'loan_status' => 'required',
            ];
            $validation->setRules($rules);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'status' => 0,
                    'message' => $validation->listErrors()
                ]);
            }

            // Prepare loan data
            $loanData = [
                'loan_code' => $this->request->getPost('loan_code'),
                'username' => $this->request->getPost('username'),
                'phone_number' => $this->request->getPost('phone_number'),
                'amount_requested' => $this->request->getPost('amount_requested'),
                'loan_status' => $this->request->getPost('loan_status'),
                'payment_status' => $this->request->getPost('payment_status'),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Insert loan
            $loan_id = $this->base_model->insert_data('loans', $loanData);

            if ($loan_id) {
                return $this->response->setJSON(['status' => 1, 'message' => 'Loan created successfully']);
            } else {
                return $this->response->setJSON(['status' => 0, 'message' => 'Failed to create loan']);
            }

        } else {
            // Load loan creation form
            $loan_code = 'LN' . strtoupper(uniqid());

            return view('loans/new_loan_form', [
                'loan_code' => $loan_code,
                'loan_statuses' => get_loan_status(),
                'payment_statuses' => get_payment_status(),
            ]);
        }
    }





}