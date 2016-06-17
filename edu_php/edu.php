<?php
require 'simple_html_dom.php';
class Edu {
    //cookie保存路径
    private $cookie;
    //学号
    private $code;
    //密码
    private $pwd;
    //固定参数
    private $schoolcode = '10611';
    //登录链接
    private $login_url = 'http://222.198.128.126/_data/index_login.aspx';
    //成绩链接
    private $score_url = 'http://222.198.128.126/xscj/Stu_MyScore_rpt.aspx';
    //成绩分布
    private $score_dis_url = 'http://222.198.128.126/xscj/Stu_cjfb_rpt.aspx';
    //考试安排
    private $exam_url = 'http://222.198.128.126/KSSW/stu_ksap_rpt.aspx';
    //考表
    private $exam_table_url = 'http://222.198.128.126/KSSW/stu_ksap_rpt.aspx';
    //考试安排页面
    private $exam_plan_url = 'http://222.198.128.126/KSSW/stu_ksap.aspx';
    //获取成绩参数
    public $score_data = array(
        'sel_xn'     => 2015, //学年
        'sel_xq'     => 0, //学期
        'SJ'         => 1,
        'btn_search' => '检索',
        'SelXNXQ'    => 2,
        'zfx_flag'   => 0,
    );
    //获取考表参数
    public $exam_data = array(
        'sel_xnxq'   => '20151',
        'sel_lc'     => '2015104,2015-2016学年第二学期13-16周',
        'btn_search' => '检索',
    );
    //获取成绩分布参数
    public $score_dis_data = array(
        'sel_xn'  => '2015',
        'sel_xq'  => '0',
        'SelXNXQ' => '2',
        'submit'  => '检索',
    );
    //考表参数
    public $exam_table_data = array(
        'sel_xnxq'   => '20151',
        'sel_lc'     => '2015104,2015-2016学年第二学期13-16周',
        'btn_search' => '检索',
    );
    //select标签所需参数
    private $select_data = array('vT' => 'stu', 'vP' => 'xnxqkslc');
    //构造函数
    public function __construct($schoolcode_, $password) {
        $this->code = $schoolcode_;
        $this->pwd  = $password;
        if (!file_exists($this->cookie)) {
            $data = $this->login();
        }
    }
    //得到select所需参数
    private function getSelectData() {
        $url    = $this->exam_plan_url;
        $result = $this->get($url);
        $html   = str_get_html($result);
        $wd     = $html->find('#thexnxqkslc');
        $id     = $html->find('option');
        foreach ($wd as $value) {
            $this->select_data['wd'] = $value->width;
        }
        foreach ($id as $value) {
            $this->select_data['id'] = $value->value;
        }
    }
    //得到考表select标签
    public function getTableSelect() {
        $this->getSelectData();
        $url  = 'http://222.198.128.126/KSSW/Private/list_xnxqkslc.aspx?id=' . $this->select_data['id'] . '&wd=' . $this->select_data['wd'] . '&vP=' . $this->select_data['vP'] . '&vT=' . $this->select_data['vT'];
        $data = iconv('GBK', 'UTF-8//IGNORE', $this->get($url));
        preg_match('/<select.*select>/', $data, $matches);
        return $matches[0];
    }
    //得到成绩select标签
    public function getScoreSelect() {
        $url    = 'http://222.198.128.126/xscj/Stu_MyScore.aspx';
        $data   = $this->get($url);
        $data   = iconv('GBK', 'UTF-8//IGNORE', $data);
        $html   = str_get_html($data);
        $result = $html->find('form');
        return $result;
    }
    //得到view参数
    private function getView() {
        $url     = $this->login_url;
        $result  = $this->get($url);
        $pattern = '/<input type="hidden" name="__VIEWSTATE" value="(.*?)" \/>/is';
        preg_match_all($pattern, $result, $matches);
        $res[0]  = $matches[1][0];
        $pattern = '/<input type="hidden" name="__VIEWSTATEGENERATOR" value="(.*?)" \/>/is';
        preg_match_all($pattern, $result, $matches);
        $res[1] = $matches[1][0];
        return $res;
    }
    //post方法得到cookie
    private function post($url, $post_data) {
        $this->cookie = dirname(__FILE__) . '\cookie_edu.txt';
        $ch           = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    //get方法
    private function get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        $result = curl_exec($ch);
        return $result;
    }
    //post方法得到数据
    private function postData($url, $post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.122 Safari/537.36 SE 2.X MetaSr 1.0');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    //密码加密函数
    private function checkPwd($code, $pwd, $schoolcode) {
        return strtoupper(substr(md5(($code . strtoupper(substr(md5($pwd), 0, 30)) . $schoolcode)), 0, 30));
    }
    //登录获取cookie
    private function login() {
        $view      = $this->getView();
        $post_data = array(
            '__VIEWSTATE'          => $view[0],
            '__VIEWSTATEGENERATOR' => $view[1],
            'Sel_Type'             => 'STU',
            'txt_dsdsdsdjkjkjc'    => $this->code,
            'efdfdfuuyyuuckjg'     => $this->checkPwd($this->code, $this->pwd, $this->schoolcode),
        );
        $url    = $this->login_url;
        $data   = iconv('GBK', 'UTF-8//IGNORE', $this->post($url, $post_data));
        $result = strpos($data, '正在加载权限数据');
        return $result;
    }
    //获取各类数据方法
    public function catchData($type) {
        switch ($type) {
            case 'score':
                $url       = $this->score_url;
                $post_data = $this->score_data;
                break;
            case 'exam':
                $url       = $this->exam_url;
                $post_data = $this->exam_data;
                break;
            case 'score_dis':
                $url       = $this->score_dis_url;
                $post_data = $this->score_dis_data;
                break;
            case 'exam_table':
                $url       = $this->exam_table_url;
                $post_data = $this->exam_table_data;
                break;
            default:
                break;
        }
        $data = iconv('GBK', 'UTF-8//IGNORE', $this->postData($url, $post_data));
        return $data;
    }
}
$my_edu = new Edu('******', '*****');
$data   = $my_edu->catchData('exam');