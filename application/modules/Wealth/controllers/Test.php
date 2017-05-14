<?php

class TestController extends BaseresumeController {
    protected $_public_id = 4;

    public function init(){
        $this->_check_token = 0;
        parent::init();
        if(!$this->checkTest()){
            $this->sendFail();
        }
    }

    public function indexAction(){
        
    }

    public function emailAction(){
        Log::debug('email', $this->_requestBody());
    }

    public function userDelAction(){
        $username = $this->_get('username');
        if($this->_get('htmlTest') == 2 && !empty($username)){
            if(Common::isMobile($username)){
                UserModel::model()->MDestroy(array('mobile' => $username));
            }elseif(Common::isEmail($username)){
                UserModel::model()->MDestroy(array('email' => $username));
            }
        }
    }

    public function targetPosition(){
        $list = ZUserTestModel::model()->MFindAllBySql(
            "SELECT rp.position_id AS id
                    ,p.name
                    ,COUNT(DISTINCT rp.resume_id) AS num
            FROM {{resume_target_position}} AS rp
            LEFT JOIN {{position}} AS p ON p.id=rp.position_id
            WHERE rp.is_del='0'
            GROUP BY rp.position_id
            HAVING num > 100
            ORDER BY num DESC"
        );
        if(!empty($list)){
            $options = array(
                array(
                    'field' => 'name',
                    'title' => 'name',
                ),
                array(
                    'field' => 'num',
                    'title' => 'num',
                ),
                array(
                    'field' => 'per',
                    'title' => 'per',
                ),
            );
            $data = array();
            $total = array_sum(array_column($list, 'num'));
            p_e($total);
            foreach ($list as $item){
                switch ($item['id']){
                    default:
                        $key = $item['id'];
                        break;
                }
                if(!isset($data[$key])){
                    $data[$key] = array(
                        'name' => $item['name'],
                        'num' => 0,
                        'per' => 0,
                    );
                }
                $data[$key]['num'] += $item['num'];
                $data[$key]['per'] = number_format( ($data[$key]['num']/$total) * 100, 2);
            }
            $data = array_values($data);
            Common::exportXls($data, $options, 'position');
        }
    }

    public function targetIndustryP(){
        $list = ZUserTestModel::model()->MFindAllBySql(
            "SELECT ri.industry_pid AS id
                    ,i.name
                    ,COUNT(DISTINCT ri.resume_id) AS num
            FROM wa_resume_industry AS ri
            LEFT JOIN wa_industry AS i ON i.id=ri.industry_pid
            WHERE ri.is_del='0'
            GROUP BY ri.industry_pid
            ORDER BY num DESC"
        );
        if(!empty($list)){
            $options = array(
                array(
                    'field' => 'name',
                    'title' => 'name',
                ),
                array(
                    'field' => 'num',
                    'title' => 'num',
                ),
                array(
                    'field' => 'per',
                    'title' => 'per',
                ),
            );
            $data = array();
            $total = array_sum(array_column($list, 'num'));
            p_e($total);
            foreach ($list as $item){
                switch ($item['id']){
                    default:
                        $key = $item['id'];
                        break;
                }
                if(!isset($data[$key])){
                    $data[$key] = array(
                        'name' => $item['name'],
                        'num' => 0,
                        'per' => 0,
                    );
                }
                $data[$key]['num'] += $item['num'];
                $data[$key]['per'] = number_format( ($data[$key]['num']/$total) * 100, 2);
            }
            $data = array_values($data);
            Common::exportXls($data, $options, 'industry_p');
        }
    }

    public function targetIndustryC(){
        $list = ZUserTestModel::model()->MFindAllBySql(
            "SELECT ri.industry_id AS id
                    ,i.name
                    ,COUNT(DISTINCT ri.resume_id) AS num
            FROM wa_resume_industry AS ri
            LEFT JOIN wa_industry AS i ON i.id=ri.industry_id
            WHERE ri.is_del='0'
            GROUP BY ri.industry_id
            ORDER BY num DESC"
        );
        if(!empty($list)){
            $options = array(
                array(
                    'field' => 'name',
                    'title' => 'name',
                ),
                array(
                    'field' => 'num',
                    'title' => 'num',
                ),
                array(
                    'field' => 'per',
                    'title' => 'per',
                ),
            );
            $data = array();
            $total = array_sum(array_column($list, 'num'));
            p_e($total);
            foreach ($list as $item){
                switch ($item['id']){
                    default:
                        $key = $item['id'];
                        break;
                }
                if(!isset($data[$key])){
                    $data[$key] = array(
                        'name' => $item['name'],
                        'num' => 0,
                        'per' => 0,
                    );
                }
                $data[$key]['num'] += $item['num'];
                $data[$key]['per'] = number_format( ($data[$key]['num']/$total) * 100, 2);
            }
            $data = array_values($data);
            Common::exportXls($data, $options, 'industry_c');
        }
    }

    public function targetSalaryMin(){
        $list = ZUserTestModel::model()->MFindAllBySql(
            "SELECT r.target_salary AS name
                    ,COUNT(1) AS num
            FROM wa_resume AS r
            WHERE r.is_del='0' AND r.finish_time>0 AND r.target_salary!=''
            GROUP BY r.target_salary
            HAVING num>100
            ORDER BY num DESC"
        );
        if(!empty($list)){
            $options = array(
                array(
                    'field' => 'name',
                    'title' => 'name',
                ),
                array(
                    'field' => 'num',
                    'title' => 'num',
                ),
                array(
                    'field' => 'per',
                    'title' => 'per',
                ),
            );
            $data = array();
            $total = array_sum(array_column($list, 'num'));
            p_e($total);
            foreach ($list as $item){
                $item['name'] = str_replace(array('~','+'), '-', $item['name']);
                $salary = explode('-', $item['name']);
                $key = !empty($salary[0]) ? $salary[0] : 'Unknown';
                if(!isset($data[$key])){
                    $data[$key] = array(
                        'name' => $key,
                        'num' => 0,
                        'per' => 0,
                    );
                }
                $data[$key]['num'] += $item['num'];
                $data[$key]['per'] = number_format( ($data[$key]['num']/$total) * 100, 2);
            }
            $data = array_values($data);
            $data = Common::sortArr($data, 'num', 'SORT_DESC');
            Common::exportXls($data, $options, 'salary_min');
        }
    }

    public function targetSalaryMaxn(){
        $list = ZUserTestModel::model()->MFindAllBySql(
            "SELECT r.target_salary AS name
                    ,COUNT(1) AS num
            FROM wa_resume AS r
            WHERE r.is_del='0' AND r.finish_time>0 AND r.target_salary!=''
            GROUP BY r.target_salary
            HAVING num>100
            ORDER BY num DESC"
        );
        if(!empty($list)){
            $options = array(
                array(
                    'field' => 'name',
                    'title' => 'name',
                ),
                array(
                    'field' => 'num',
                    'title' => 'num',
                ),
                array(
                    'field' => 'per',
                    'title' => 'per',
                ),
            );
            $data = array();
            $total = array_sum(array_column($list, 'num'));
            p_e($total);
            foreach ($list as $item){
                $item['name'] = str_replace(array('~','+'), '-', $item['name']);
                $salary = explode('-', $item['name']);
                $key = !empty($salary[1]) ? $salary[1] : (!empty($salary[0]) ? $salary[0]+1000 : 'Unknown');
                if(!isset($data[$key])){
                    $data[$key] = array(
                        'name' => $key,
                        'num' => 0,
                        'per' => 0,
                    );
                }
                $data[$key]['num'] += $item['num'];
                $data[$key]['per'] = number_format( ($data[$key]['num']/$total) * 100, 2);
            }
            $data = array_values($data);
            $data = Common::sortArr($data, 'num', 'SORT_DESC');
            Common::exportXls($data, $options, 'salary_max');
        }
    }

}
