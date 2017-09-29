<?php

class Home_admin_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function loginCheck($values)
    {
        $arr = array(
            'username' => $values['username'],
            'password' => md5($values['password']),
        );
        $this->db->where($arr);
        $result = $this->db->get('users');
        $resultArray = $result->row_array();
        if ($result->num_rows() > 0) {
            $this->db->where('id', $resultArray['id']);
            $this->db->update('users', array('last_login' => time()));
        }
        return $resultArray;
    }

    /*
     * Some statistics methods for home page of
     * administration
     * START
     */

    public function countLowQuantityProducts()
    {
        $this->db->where('quantity <=', 5);
        return $this->db->count_all_results('products');
    }

    public function lastSubscribedEmailsCount()
    {
        $yesterday = strtotime('-1 day', time());
        $this->db->where('time > ', $yesterday);
        return $this->db->count_all_results('subscribed');
    }

    public function getMostSoldProducts($limit = 10)
    {
        $this->db->select('url, procurement');
        $this->db->order_by('procurement', 'desc');
        $this->db->where('procurement >', 0);
        $this->db->limit($limit);
        $queryResult = $this->db->get('products');
        return $queryResult->result_array();
    }

    public function getReferralOrders()
    {

        $this->db->select('count(id) as num, clean_referrer as referrer');
        $this->db->group_by('clean_referrer');
        $queryResult = $this->db->get('orders');
        return $queryResult->result_array();
    }

    public function getOrdersByPaymentType($limit = 10)
    {
        $this->db->select('count(id) as num, payment_type');
        $this->db->group_by('payment_type');
        $this->db->limit($limit);
        $queryResult = $this->db->get('orders');
        return $queryResult->result_array();
    }

    public function getOrdersByMonth()
    {
        $result = $this->db->query("SELECT YEAR(FROM_UNIXTIME(date)) as year, MONTH(FROM_UNIXTIME(date)) as month, COUNT(id) as num FROM orders GROUP BY YEAR(FROM_UNIXTIME(date)), MONTH(FROM_UNIXTIME(date)) ASC");
        $result = $result->result_array();
        $orders = array();
        $years = array();
        foreach ($result as $res) {
            if (!isset($orders[$res['year']])) {
                for ($i = 1; $i <= 12; $i++) {
                    $orders[$res['year']][$i] = 0;
                }
            }
            $years[] = $res['year'];
            $orders[$res['year']][$res['month']] = $res['num'];
        }
        return array(
            'years' => array_unique($years),
            'orders' => $orders
        );
    }

    /*
     * Some statistics methods for home page of
     * administration
     * END
     */

    public function setValueStore($key, $value)
    {
        $this->db->where('thekey', $key);
        $query = $this->db->get('value_store');
        if ($query->num_rows() > 0) {
            $this->db->where('thekey', $key);
            $this->db->update('value_store', array('value' => $value));
        } else {
            $this->db->insert('value_store', array('value' => $value, 'thekey' => $key));
        }
    }

    public function getTranslations($id, $type)
    {
        $this->db->where('for_id', $id);
        $this->db->where('type', $type);
        $query = $this->db->select('*')->get('translations');
        $arr = array();
        foreach ($query->result() as $row) {
            $arr[$row->abbr]['title'] = $row->title;
            $arr[$row->abbr]['basic_description'] = $row->basic_description;
            $arr[$row->abbr]['description'] = $row->description;
            $arr[$row->abbr]['price'] = $row->price;
            $arr[$row->abbr]['old_price'] = $row->old_price;
        }
        return $arr;
    }

    public function changePass($new_pass, $username)
    {
        $this->db->where('username', $username);
        $result = $this->db->update('users', array('password' => md5($new_pass)));
        return $result;
    }

    public function getValueStore($key)
    {
        $query = $this->db->query("SELECT value FROM value_store WHERE thekey = '$key'");
        $img = $query->row_array();
        return $img['value'];
    }

    public function newOrdersCheck()
    {
        $result = $this->db->query("SELECT count(id) as num FROM `orders` WHERE viewed = 0");
        $row = $result->row_array();
        return $row['num'];
    }

    public function setCookieLaw($post)
    {
        $query = $this->db->query('SELECT id FROM cookie_law');
        if ($query->num_rows() == 0) {
            $id = 1;
        } else {
            $result = $query->row_array();
            $id = $result['id'];
        }

        $this->db->replace('cookie_law', array(
            'id' => $id,
            'link' => $post['link'],
            'theme' => $post['theme'],
            'visibility' => $post['visibility']
        ));
        $for_id = $this->db->insert_id();

        $i = 0;
        foreach ($post['translations'] as $translate) {
            $this->db->replace('cookie_law_translations', array(
                'message' => htmlspecialchars($post['message'][$i]),
                'button_text' => htmlspecialchars($post['button_text'][$i]),
                'learn_more' => htmlspecialchars($post['learn_more'][$i]),
                'abbr' => $translate,
                'for_id' => $for_id
            ));
            $i++;
        }
    }

    public function getCookieLaw()
    {
        $arr = array('cookieInfo' => null, 'cookieTranslate' => null);
        $query = $this->db->query('SELECT * FROM cookie_law');
        if ($query->num_rows() > 0) {
            $arr['cookieInfo'] = $query->row_array();
            $query = $this->db->query('SELECT * FROM cookie_law_translations');
            $arrTrans = $query->result_array();
            foreach ($arrTrans as $trans) {
                $arr['cookieTranslate'][$trans['abbr']] = array(
                    'message' => $trans['message'],
                    'button_text' => $trans['button_text'],
                    'learn_more' => $trans['learn_more']
                );
            }
        }
        return $arr;
    }

}