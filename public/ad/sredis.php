<?php

class Redisutil 
{
    protected $handler = null;
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'timeout'    => 1,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
    ];
    protected $tag;

    // 主库 写库 117.34.72.95 到96
    public $db_link='mysql:host=127.0.0.1;dbname=lezunsys;port=3306';
    public $db_root='root';
    public $db_password='123456';

    // 从库读 117.34.72.95 到96
    public $db_syn_link = 'mysql:host=172.20.1.97;dbname=netlezun;port=3306';
    public $db_syn_root='sel_user_95';
    public $db_syn_password='xya197a3321';

    //  计费统计连接主库
    public $db_pv_link='mysql:host=127.0.0.1;dbname=lezunsys;port=3306';
    public $db_pv_root='root';
    public $db_pv_password='123456';

    public $curl_url='http://117.34.72.95/ad/v.php';

    public $log_s_file = '/home/errorlog/slog.txt';
    public $log_c_file = '/home/errorlog/clog.txt';

    public $cilck_url = 'blog/c';
    public $sign = '?';

    public $redirect_url = 'www.jystea.com';

    //自营广告id
    public $self_adv_id = '5064,1021';

    public $time_spacing = 20000;

    //广告滑动的开关 0：关闭 1：开启
    public $switch = 1;


    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
       if (!extension_loaded('redis')) {
           throw new \BadFunctionCallException('not support: redis');
       }
       if (!empty($options)) {
           $this->options = array_merge($this->options, $options);
      }
       $func          = $this->options['persistent'] ? 'pconnect' : 'connect';
       $this->handler = new \Redis;
       
       //test

       $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

       if ('' != $this->options['password']) {
           $this->handler->auth($this->options['password']);
       }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->get($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value)) {
            return $default;
        }
        $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据 byron sampson<xiaobo.sun@qq.com>
        return (null === $jsonData) ? $value : $jsonData;
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key = $this->getCacheKey($name);
        //对数组/对象数据进行缓存处理，保证数据完整性  byron sampson<xiaobo.sun@qq.com>
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->delete($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flushDB();
    }

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * 获取实际的缓存标识
     * @access public
     * @param string $name 缓存名
     * @return string
     */
    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }


    /**
     * 缓存标签
     * @access public
     * @param string        $name 标签名
     * @param string|array  $keys 缓存标识
     * @param bool          $overlay 是否覆盖
     * @return $this
     */
    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($keys)) {
            $this->tag = $name;
        } else {
            $key = 'tag_' . md5($name);
            if (is_string($keys)) {
                $keys = explode(',', $keys);
            }
            $keys = array_map([$this, 'getCacheKey'], $keys);
            if ($overlay) {
                $value = $keys;
            } else {
                $value = array_unique(array_merge($this->getTagItem($name), $keys));
            }
            $this->set($key, implode(',', $value));
        }
        return $this;
    }

    /**
     * 获取标签包含的缓存标识
     * @access public
     * @param string $tag 缓存标签
     * @return array
     */
    protected function getTagItem($tag)
    {
        $key   = 'tag_' . md5($tag);
        $value = $this->get($key);
        if ($value) {
            return explode(',', $value);
        } else {
            return [];
        }
    }

    /**
     * 切换到指定的数据库索引值
     * dbname 用数字值指定，（默认为 0）我们以 1 作为起始索引值。
     *
     */
    public function select($dbname)
    {
        return $this->handler->select($dbname);
    }
}
