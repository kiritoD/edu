import hashlib
import re
import mysql.connector
import requests
from bs4 import BeautifulSoup
from mail import mail


class edu(object):
    # 固定参数
    __schoolcode = '10611'
    # 登录链接
    __login_url = 'http://222.198.128.126/_data/index_login.aspx'
    # 成绩链接
    __score_url = 'http://222.198.128.126/xscj/Stu_MyScore_rpt.aspx'
    # 成绩分布
    __score_dis_url = 'http://222.198.128.126/xscj/Stu_cjfb_rpt.aspx'
    # 考试安排
    __exam_url = 'http://222.198.128.126/KSSW/stu_ksap_rpt.aspx'
    # 考表
    __exam_table_url = 'http://222.198.128.126/KSSW/stu_ksap_rpt.aspx'
    # 获取成绩参数
    score_data = {
        'sel_xn': 2015,  # 学年
        'sel_xq': 1,  # 学期
        'SJ': 0,  # 0为原始成绩，1为有效成绩
        'btn_search': '检索',
        'SelXNXQ': 2,
        'zfx_flag': 0,
    }
    # 获取考表参数
    exam_data = {
        'sel_xnxq': '20151',
        'sel_lc': '2015104,2015-2016学年第二学期13-16周',
        'btn_search': '检索',
    }
    # 获取成绩分布参数
    score_dis_data = {
        'sel_xn': '2015',
        'sel_xq': '0',
        'SelXNXQ': '2',
        'submit': '检索',
    }
    # 考表参数
    exam_table_data = {
        'sel_xnxq': '20151',
        'sel_lc': '2015104,2015-2016学年第二学期13-16周',
        'btn_search': '检索',
    }

    def __init__(self, code, password):
        super(edu, self).__init__()
        self.__code = code
        self.__pwd = password
        self.login()

    # 得到view参数

    def __getView(self):
        view = []
        r = re.compile(
            r'<input type="hidden" name="__VIEWSTATE" value="(.*?)" \/>')
        data = requests.get(self.__login_url)
        view = r.findall(data.text)
        r = re.compile(
            r'<input type="hidden" name="__VIEWSTATEGENERATOR" value="(.*?)" \/>')
        data = r.findall(data.text)
        view.append(data[0])
        return view

    # 加密函数

    def __checkPwd(self):
        p = hashlib.md5(self.__pwd.encode()).hexdigest()
        p = hashlib.md5(
            (self.__code + p[0:30].upper() + self.__schoolcode).encode()).hexdigest()
        return p[0:30].upper()

    # 登录获取cookie

    def login(self):
        view = self.__getView()
        params = {'__VIEWSTATE': view[0],
                  '__VIEWSTATEGENERATOR': view[1],
                  'Sel_Type': 'STU',
                  'txt_dsdsdsdjkjkjc': self.__code,
                  'efdfdfuuyyuuckjg': self.__checkPwd(), }
        r = requests.post(self.__login_url, data=params)
        if u'正在加载权限数据' in r.text:
            print('登录成功！')
            print('学号为：' + self.__code + ',密码为：' + self.__pwd)
            self.__cookies = r.cookies
            return True

    # post获取数据

    def __post(self, url, data):
        return requests.post(
            url, data, cookies=self.__cookies).text

    # 获取数据

    def catchData(self, type):
        result = {'score': self.__post(self.__score_url, self.score_data), 'exam': self.__post(self.__exam_url, self.exam_data), 'score_dis': self.__post(
            self.__score_dis_url, self.score_dis_data), 'exam_table': self.__post(self.__exam_table_url, self.exam_table_data), }
        return result.get(type)

    # 插入数据库

    def insert_score(self, X):
        conn = mysql.connector.connect(
            host='localhost', user='root', password='113789', database='mail')
        cur = conn.cursor()
        for tr in X:
            d = []
            for td in tr:
                d.append(td.text)
            sql = "select * from score where id='%s'" % d[0]
            cur.execute(sql)
            a = cur.fetchall()
            if not a:
                sql = "insert into score(id,course,credit,type,method,quality,score,mark) values('%s','%s','%s','%s','%s','%s','%s','%s')" % (
                    d[0], d[1], d[2], d[3], d[4], d[5], d[6], d[7])
                cur.execute(sql)
                conn.commit()
            else:
                if not d[6] in a[0]:
                    sql = "update score set score='%s' where id='%s'" % (d[6], d[
                        0])
                    cur.execute(sql)
                    conn.commit()
                    email = mail()
                    email.textMail('******', '有成绩更新了', tr)

        cur.close()
        conn.close()
    # 爬取成绩并存入数据库

    def get_score(self):
        data = self.catchData('score')
        bs4 = BeautifulSoup(data, 'lxml')
        B = bs4.find_all(class_='B')
        H = bs4.find_all(class_='H')
        self.insert_score(B)
        self.insert_score(H)

my_edu = edu('****', '*****')
