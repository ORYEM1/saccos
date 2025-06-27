<?php

namespace App\Models;
use CodeIgniter\Model;
class DataModel extends Model
{
    protected $db;
    protected $download_limit=500;
    public function __construct()
    {
        $this->db=db_connect();
    }
    private function get_data($params)
    {
        $return_data=array();
        $query_params=array();
        if(!isset($params['main_table']))
        {
            exit("Main table not set");
        }
        $table=$params['main_table'];
        if(isset($params['query_params']))
        {
            $query_params=$params['query_params'];
        }
        if(isset($query_params['draw']))
        {
            $return_data['draw']=$query_params['draw'];
        }
        $builder=$this->db->table($table);
        if(isset($params['fields']))
        {
            $fields=implode(',',qualify_columns($params['main_table'],$params['fields']));
            if(isset($params['auto_escape']))
            {
                if(!$params['auto_escape'])
                {
                    $builder->select($fields,false);
                }
                else
                {
                    $builder->select($fields);
                }
            }
            else
            {
                $builder->select($fields);
            }
        }
        if(isset($params['join'])&& is_array($params['join']))
        {
            foreach($params['join'] as $join)
            {
                if(isset($join[0]) && isset($join[1]) && isset($join[2]))
                {
                    $builder->join($join[0],$join[1],$join[2]);
                }
                else if(isset($join[0]) && isset($join[1]))
                {
                    $builder->join($join[0],$join[1]);
                }
                else if(isset($join['table']) && isset($join['condition']) && isset($join['type']))
                {
                    $builder->join($join['table'],$join['condition'],$join['type']);
                }
                else if(isset($join['table']) && isset($join['condition']))
                {
                    $builder->join($join['table'],$join['condition']);
                }
                else if(isset($join['table']) && isset($join['on']) && isset($join['type']))
                {
                    $builder->join($join['table'],$join['on'],$join['type']);
                }
                else if(isset($join['table']) && isset($join['on']))
                {
                    $builder->join($join['table'],$join['on']);
                }
            }
        }
        if(!empty($_SESSION["search_{$table}"]))
        {
            $builder->where($_SESSION["search_{$table}"]);
        }
        if(!empty($_SESSION["search_{$table}_like"]))
        {
            $builder->like($_SESSION["search_{$table}_like"]);
        }
        if(!empty($_SESSION["search_{$table}_where_in"]))
        {
            foreach($_SESSION["search_{$table}_where_in"] as $field=>$values)
            {
                if(!is_array($values))
                {
                    $values=explode(",",$values);
                }
                $builder->whereIn($field,$values);
            }
        }
        $return_data['recordsTotal']=$builder->countAllResults(false);

        if(isset($query_params['search_term'])&&$query_params['search_term']!=''&&isset($params['searchable_fields']))
        {
            if(isset($params['searchable_fields'])&&is_array($params['searchable_fields']))
            {
                foreach ($params['searchable_fields'] as $key=>$field)
                {
                    $field_parts=explode(".",$field);
                    if(count($field_parts)==1)
                    {
                        $params['searchable_fields'][$key]=$params['main_table'].'.'.$field_parts[0];
                    }
                }
            }
            $searchable_fields=$params['searchable_fields'];
            $search_term=$this->db->escapeString($query_params['search_term']);
            if(!empty($searchable_fields))
            {
                $where='(';
                $total_fields=count($searchable_fields);
                $counter=1;
                foreach($searchable_fields as $field)
                {
                    $where.=" {$field} LIKE '%{$search_term}%' ";
                    if($counter<$total_fields) $where.=" OR ";
                    ++$counter;
                }
                $where.=')';
                $builder->where($where);
            }
        }
        $return_data['recordsFiltered']=$builder->countAllResults(false);
        /*if(!empty($_GET['oc']))
        {
            $params['order_fields']=json_decode(base64_decode($_GET['oc']),true);
        }*/
        if(isset($params['order_fields'])&&is_array($params['order_fields']))
        {
            foreach ($params['order_fields'] as $key=>$field)
            {
                $field_parts=explode(".",$field);
                if(count($field_parts)==1)
                {
                    $params['order_fields'][$key]=$params['main_table'].'.'.$field_parts[0];
                }
            }
        }

        if(isset($params['group']))
        {
            if(is_array($params['group']))
            {
                foreach($params['group'] as $group)
                {
                    $builder->groupBy($group);
                }
            }
            else
            {
                $builder->groupBy($params['group']);
            }
        }

        if(isset($query_params['order'])&&isset($params['order_fields']))
        {
            $columns=$params['order_fields'];
            foreach($query_params['order'] as $order_field)
            {
                if(isset($columns[$order_field['column']]))
                {
                    $builder->orderBy($columns[$order_field['column']],$order_field['dir']);
                }
            }
        }
        if(empty($query_params)) $builder->limit($this->download_limit);
        else if(isset($query_params['limit']))
        {
            $builder->limit($query_params['limit']);
        }
        else $builder->limit($query_params['length'],$query_params['start']);
        //echo $builder->getCompiledSelect(); exit;
        $query=$builder->get();
        $data=$query->getResultArray();
        $response=array();
        $response['response_data']=$return_data;
        $response['db_data']=$data;
        return $response;
    }
    public function get_users($parameters=array())
     {
         $params=array();
         $params['query_params']=$parameters;
         $params['main_table']='users';
         $params['searchable_fields']=array('first_name','last_name','username','phone_number');
         $params['fields']=array('id','first_name','last_name','phone_number','user_roles.role','status','gender','date_of_birth','email','address','supportive_document','comment');
         $params['join']=array(array('table'=>'user_roles','condition'=>'users.role=user_roles.id','type'=>'left'));
         $query=$this->get_data($params);
         if(isset($parameters['download_data'])&&$parameters['download_data'])
         {
             return $query['db_data'];
         }
         $return_data=$query['response_data'];
         $return_data['data']=array();
         $data=$query['db_data'];
         $statuses=get_statuses_array(true);
         foreach($data as $record)
         {
             $row=array(
                 $record['id'],
                 $record['first_name'],
                 $record['last_name'],
                 $record['role'],
                 $record['username'],
                 $record['phone_number'],
                 $statuses[$record['status']]??$record['status'],
                 $record['gender'],
                 $record['email'],
                 $record['address'],
                 $record['date_of_birth'],
                 $record['supportive_document'],
                 $record['comment'],
                 );


             $url="/users/view_user/{$record['id']}";
             $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

             $url="/users/edit_user/{$record['id']}";
             $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

             $url="/users/reset_password/{$record['id']}";
             $row[]="<a href='{$url}'  title='Reset Password' class='open_modal'><i class='fa fa-unlock'></i></a>";

             $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";

             $return_data['data'][]=$row;
         }
         return $return_data;

     }

    public function get_roles($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='user_roles';
        $params['fields']=array('id','role','role_type','status');
        $params['searchable_fields']=array('role');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['role'],$record['role_type'],$statuses[$record['status']]??$record['status']);
            $url="/roles/view_role/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/roles/edit_role/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_edited_data_log($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='edited_data_log';
        $params['fields']=array('id','ip_address','users.first_name','users.other_names','date_time','table','record_id');
        $params['join']=array(array('table'=>'users','condition'=>'edited_data_log.user_id=users.id','type'=>'left'));
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        foreach($data as $record)
        {

            $row=array($record['id'],$record['date_time'],$record['ip_address'],$record['table'],$record['record_id'],$record['first_name'].' '.$record['other_names']);
            $url=base_url("edited_data_log/view_log/{$record['id']}");
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";
            $return_data['data'][]=$row;
        }
        return $return_data;
    }
    public function get_activity_log($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='activity_log';
        $params['fields']=array('id','ip_address','activity','users.first_name','users.other_names','date_time');
        $params['join']=array(array('table'=>'users','condition'=>'activity_log.user_id=users.id','type'=>'left'));
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        foreach($data as $record)
        {

            $row=array($record['id'],$record['date_time'],$record['ip_address'],$record['first_name'].' '.$record['other_names'],$record['activity']);
            $url=base_url("activity_log/view_log/{$record['id']}");
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";
            $return_data['data'][]=$row;
        }
        return $return_data;
    }
    public function get_loans($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='loans';
        $params['fields']=array('id', 'country_code', 'dialing_code', 'telephone_number_length', 'country', 'supported');
        $params['searchable_fields']=array('country');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['country'],(strlen($record['country_code'])>0)?$record['country_code']:'NA',(strlen($record['dialing_code'])>0)?$record['dialing_code']:'NA',$statuses[$record['supported']]??$record['supported'],(strlen($record['telephone_number_length'])>0)?$record['telephone_number_length']:'NA');
            $url="/countries/view_country/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/countries/edit_country/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_savings($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='savings';
        $params['fields']=array('id', 'username', 'phone_number', 'payment_method', 'status','amount_deposited','total_amount','status','date','created_by');
        $params['searchable_fields']=array('username','id','phone_number');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
       // $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array(
                $record['id'],
                $record['username'],
                $record['phone_number'],
                $record['payment_method'],
                $record['amount_deposited'],
                $record['total_amount'],
                $record['status'],
                $record['date'],
                $record['created_by']);
            $url="/savings/view_savings/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/savings/edit_savings/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_wallets($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='wallets';
        $params['searchable_fields']=array('wallet_name','currency','code','countries.country');
        $params['fields']=array('id', 'code', 'status', 'collection_status', 'disbursement_status', 'collection_api', 'disbursement_api', 'collection_lib.readable_name AS collection_api_name', 'disbursement_lib.readable_name AS disbursement_api_name','countries.country');
        $params['join'][]=array('table'=>'countries','condition'=>'countries.id=wallets.country_id','type'=>'left');
        $params['join'][]=array('table'=>'wallet_libraries collection_lib','condition'=>'collection_lib.id=wallets.collection_api','type'=>'left');
        $params['join'][]=array('table'=>'wallet_libraries disbursement_lib','condition'=>'disbursement_lib.id=wallets.disbursement_api','type'=>'left');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array(
                $record['id'],
                $record['code'],
                (strlen($record['collection_api_name'])>0)?$record['collection_api_name']:$record['collection_api'],
                (strlen($record['disbursement_api_name'])>0)?$record['disbursement_api_name']:$record['disbursement_api'],
                $statuses[$record['status']]??$record['status'],
                $statuses[$record['collection_status']]??$record['collection_status'],
                $statuses[$record['disbursement_status']]??$record['disbursement_status'],
            );
            $url="/wallets/view_wallet/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/wallets/edit_wallet/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";

            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_logged_numbers($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='logged_numbers';
        $params['fields']=array('id', 'date_time_created', 'number', 'wallet_id', 'created_by', 'wallets.wallet_name AS wallet_name');
        $params['join']=array(array('table'=>'wallets','condition'=>'wallets.id=logged_numbers.wallet_id','type'=>'left'));
        $params['searchable_fields']=array('id', 'number');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['date_time_created'],$record['number'],$record['wallet_name']);
            $url="/logged_numbers/view_logged_number/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/logged_numbers/edit_logged_number/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_supported_currencies($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='supported_currencies';
        $params['fields']=array('id', 'code', 'name');
        $params['searchable_fields']=array('code','name');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['code'],$record['name']);
            $url="/supported_currencies/view_currency/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/supported_currencies/edit_currency/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_network_prefixes($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='mobile_network_prefixes';
        $params['join']=array(array('table'=>'wallets','condition'=>'mobile_network_prefixes.wallet_id=wallets.id','type'=>'left'));
        $params['fields']=array('id', 'prefix', 'date_time_created','wallets.wallet_name');
        $params['searchable_fields']=array('prefix','wallets.wallet_name');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['prefix'],$record['wallet_name'],$record['date_time_created']);
            $url="/mobile_network_prefixes/view_prefix/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/mobile_network_prefixes/edit_prefix/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_whitelisted_user_ips($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='whitelisted_user_ips';
        $params['fields']=array('id', 'name', 'pool','date_time_created');
        $params['searchable_fields']=array('name');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['name'],$record['date_time_created']);
            $url="/whitelisted_user_ips/view_pool/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/whitelisted_user_ips/edit_pool/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_wallet_libraries($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='wallet_libraries';
        $params['join'][]=array('table'=>'wallets','condition'=>'wallet_libraries.wallet_id=wallets.id','type'=>'left');
        $params['join'][]=array('table'=>'countries','condition'=>'wallet_libraries.country_id=countries.id','type'=>'left');
        $params['fields']=array('id', 'status','readable_name','log_requests','log_type','wallets.wallet_name','countries.country');
        $params['searchable_fields']=array('wallet_libraries.readable_name','wallets.wallet_name','countries.country');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array(
                $record['id'],
                $record['readable_name'],
                (!empty($record['wallet_name']))?$record['wallet_name']:'NA',
                (!empty($record['country']))?$record['country']:'NA',
                $statuses[$record['status']]??$record['status'],
                $statuses[$record['log_requests']]??$record['log_requests']
            );
            $url="/wallet_libraries/view_library/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/wallet_libraries/edit_library/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_transaction_accounts($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='transaction_accounts';
        $params['join']=array(array('table'=>'wallet_libraries','condition'=>'transaction_accounts.library_id=wallet_libraries.id','type'=>'left'));
        $params['fields']=array('id', 'account_number', 'purpose', 'status', 'wallet_libraries.readable_name');
        $params['searchable_fields']=array('account_number','wallet_libraries.readable_name');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            $row=array($record['id'],$record['account_number'],$record['purpose'],$record['readable_name'],$statuses[$record['status']]??$record['status']);
            $url="/transaction_accounts/view_account/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/transaction_accounts/edit_account/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;

    }
    public function get_transaction_parameters($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='transaction_parameters';
        $params['join'][]=array('table'=>'wallet_libraries','condition'=>'transaction_parameters.library_id=wallet_libraries.id','type'=>'left');
        $params['fields']=array('id','name','value','date_time_updated','expiry_time', 'library_id', 'wallet_libraries.readable_name');
        $params['searchable_fields']=array('name','wallet_libraries.readable_name');
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        $statuses=get_statuses_array(true);
        foreach($data as $record)
        {
            if(strlen($record['value'])>36)
            {
                $value=substr($record['value'],0,36).'...';
            }
            else
            {
                $value=$record['value'];
            }
            $value_url="/transaction_parameters/view_parameter_value/{$record['id']}";
            $library_url="/wallet_libraries/view_library/{$record['library_id']}";
            $row=array(
                $record['id'],
                "<a href='{$library_url}'  title='View' class='open_modal'>{$record['readable_name']}</a>",
                "<code class='transaction_param_name'>{$record['name']}</code>",
                "<a href='{$value_url}'  title='View' class='open_modal'><code class='transaction_param_value'>{$value}</code></a>",
                !empty($record['date_time_updated'])?$record['date_time_updated']:'NA',
                !empty($record['expiry_time'])?$record['expiry_time']:'NA',
            );

            $url="/transaction_parameters/view_parameter/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";

            $url="/transaction_parameters/edit_parameter/{$record['id']}";
            $row[]="<a href='{$url}'  title='Edit' class='open_modal'><i class='fa fa-edit'></i></a>";

            $row[]="<input type='checkbox' class='select_record' data-id='{$record['id']}'>";
            $return_data['data'][]=$row;
        }
        return $return_data;
    }
    public function get_transaction_request_log($parameters=array())
    {
        $params=array();
        $params['query_params']=$parameters;
        $params['main_table']='transaction_request_log';
        $params['fields']=array('id', 'transacting_number', 'library_id', 'request_id', 'date_time', 'initiated_by', 'function','wallet_libraries.readable_name');
        $params['join']=array(array('table'=>'wallet_libraries','condition'=>'transaction_request_log.library_id=wallet_libraries.id','type'=>'left'));
        $query=$this->get_data($params);
        if(isset($parameters['download_data'])&&$parameters['download_data'])
        {
            return $query['db_data'];
        }
        $return_data=$query['response_data'];
        $return_data['data']=array();
        $data=$query['db_data'];
        foreach($data as $record)
        {
            $library_url="/wallet_libraries/view_library/{$record['library_id']}";
            $row=array(
                $record['id'],
                $record['date_time'],
                (!(empty($record['request_id'])))?$record['request_id']:'NA',
                (!(empty($record['transacting_number'])))?$record['transacting_number']:'NA',
                "<a href='{$library_url}'  title='View' class='open_modal'>{$record['readable_name']}</a>",
                $record['initiated_by'],
                $record['function']
            );
            $url="transaction_request_log/view_log/{$record['id']}";
            $row[]="<a href='{$url}'  title='View' class='open_modal'><i class='fa fa-search'></i></a>";
            $return_data['data'][]=$row;
        }
        return $return_data;
    }
}
