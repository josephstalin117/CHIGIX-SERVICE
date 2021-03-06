<?php

/**
 * 千木控制器扩展
 *
 * @author Richard Lea <chigix@zoho.com>
 * @version 3.1.2 <ThinkPHP控制器扩展，为千木Service架构提供接口，所有千木特定接口，均以public function__chigiXXXX()形式定义>
 */
abstract class ChigiAction extends Action {

    public function __construct() {
        $this->__chigiCheckURL();
        $this->__chigiEmptyRedirection();
        parent::__construct();
    }

    //多重空检测与高级跳转
    private function __chigiEmptyRedirection() {
        if (get_class($this) == 'EmptyAction') {
            //控制器不存在，则进行空控制器跳转
            switch (substr(MODULE_NAME, 0, 2)) {
                case 'On':
                    //on当模块接收
                    if (isset($_GET['type'])) {
                        // 进行除表单接收外的其他系统特定操作
                        $type = $_GET['type'];
                        return $this->$type();
                    } else {
                        // 进入表单接收操作on
                        return($this->on());
                    }
                    break;
                default:
                    // <editor-fold defaultstate="collapsed" desc="查询全局页面定义">
                    $result = array();
                    $isInt = intval(MODULE_NAME);
                    $pageName = MODULE_NAME;
                    if ($isInt > 0) {
                        $result = M('ChigiPage')->field('pagename,domain,protocol')->find($isInt);
                        $pageName = $result['pagename'];
                    } else {
                        $result = M('ChigiPage')->field('domain,protocol')->where(array('pagename' => $pageName, 'status' => 1))->find();
                    }
                    //查询结果处理
                    if ($result === null) {
                        //没有定义的全局控制器，则执行空模块内的逻辑
                        return;
                    } else {
                        //已找到对应的全局控制器，准备进行跳转
                        if (isset($_GET['_URL_']))
                            unset($_GET['_URL_']);
                        header('HTTP/1.1 301 Moved Permanently'); //发出301头部
                        return(redirectHeader(MODULE_NAME . '/' . ACTION_NAME, $_GET, $result['protocol'] . '://' . $result['domain']));
                    }
                    // </editor-fold>
                    break;
            }
        } elseif (method_exists($this, ACTION_NAME)) {
            //如果目标操作直接在当前控制器中
            return;
        } elseif (substr(ACTION_NAME, 0, 2) == 'on') {
            //on当操作接收
            //目标方法在当前控制器中没有重写，且以on开头
            return($this->on());
        } else {
            return;
        }
    }

    /**
     * 表单提交统一接收操作
     *
     * @param string $serviceName
     * @param string $methodName
     * @param string $successDirect
     * @param string $errorDirect
     * @return void
     */
    public function on(
    $serviceName = null, $methodName = null, $successDirect = null, $errorDirect = null, $sucAlert = null, $errAlert = null
    ) {
        //对表单进行安全令牌验证：
        if (!M()->autoCheckToken($_POST)) {
            _404();
        }
        unset($_POST[C("TOKEN_NAME")]);
        if (isset($_SESSION['verify'])) {
            if ($_SESSION['verify'] != md5($_POST['verify'])) {
                $this->error("验证码错误");
            }
            unset($_POST['verify']);
        }
        //对于15分钟内简单表单，进行CHING会话接收逻辑
        if ($serviceName === null) {
            if (ching('CHIGI_TAG') === null) {
                //操作超时
                $alert = new ChigiAlert(array(
                            'status' => 401,
                            'info' => '对不起，操作超时'
                        ));
                $alert->alert();
                return(redirectHeader($_SERVER['HTTP_REFERER']));
            } else {
                //本操作暴露于HTTP下执行
                $serviceName = ching("CHIGI_TAG.SERVICE");
                $methodName = ching("CHIGI_TAG.METHOD");
            }
        }
        $service = service($serviceName);
        //优先捕获iframe
        if ($_GET['iframe'])
            $successDirect = $_GET['iframe'];
        $service->setDirect($successDirect, $errorDirect);
        if (method_exists($service, $methodName)) {
            //调用开发者定义的service请求
            $result = $service->$methodName();
        }  else {
            //支持直接请求，service中可免写请求定义
            if ('on' == substr($methodName, 0, 2)) {
                $methodName = substr($methodName, 2);
            }
            $result = $service->request($_POST, $methodName);
        }
        // <editor-fold defaultstate="collapsed" desc="将非int型的$result根据返回值规范变换为-1,0,1">
        if (is_object($result) && 'ChigiReturn' == get_class($result)) {
            $result = $result->isValid() ? 1 : 0;
        } elseif (is_array($result) && isset($result['status'])) {
            $result = $result['status'] < 300 ? 1 : 0;
        }
        // </editor-fold>
        switch ($result) {
            case false:
            case 0:
                return($service->errorDirectHeader($errAlert));
                break;
            case true:
            case 1:
                return($service->successDirectHeader($sucAlert));
                break;
            case -1:
                //DEBUG，不跳转
                echo '<h1>ON操作调试模式</h1><br/><h2>Result返回结果：</h2><br/>';
                dump($result);
                echo '<h2>当前Service状态：</h2></br>';
                dump($service);
                B('ShowPageTrace');
                return;
                break;
            default:
                //非DEBUG，不跳转，直接返回
                //主用于兼容向下兼容旧版本的on接口写法
                return;
                break;
        }
        //return();
    }

    public function __chigiFetch() {
        $this->fetch();
    }

    public function __chigiShow($content = "") {
        $this->show($content);
    }

//-----------------------------------------------------
//---原生方法重写   --------------------------
//-----------------------------------------------------
    protected function show($content, $charset = '', $contentType = '', $prefix = '') {
        parent::show($content, $charset, $contentType, $prefix);
    }

    protected function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
        // <editor-fold defaultstate="collapsed" desc="初始化视图类，摘自Action类initView方法">
        //实例化视图类
        if (!$this->view)
            $this->view = Think::instance('View');
        // 模板变量传值
        if ($this->tVar)
            $this->view->assign($this->tVar);
        // </editor-fold>
        // <editor-fold defaultstate="collapsed" desc="摘自View类display方法">
        G('viewStartTime');
        tag('view_begin', $templateFile);
        $output = $this->view->fetch($templateFile, $content, $prefix);
        // </editor-fold>
        //★输出前端页面HTML代码至浏览器
        // <editor-fold defaultstate="collapsed" desc="摘自View类render方法，请视当前版本的render方法进行改动">
        $charset = C('DEFAULT_CHARSET');
        $contentType = C('TMPL_CONTENT_TYPE');
        // 网页字符编码
        header('Content-Type:' . $contentType . '; charset=' . $charset);
        header('Cache-control: ' . C('HTTP_CACHE_CONTROL'));  // 页面缓存控制
        header('X-Powered-By:CHIGIX.com');
        echo $output;
        // </editor-fold>
        tag('view_end');
    }

    protected function fetch($templateFile = '', $content = '', $prefix = '') {
        parent::fetch($templateFile, $content, $prefix);
    }

    /**
     * 针对类中非public方法的调用
     *
     * 使用示例：$obj->__chigiCaller("display",array("index"));
     * @param string $method
     * @param array $args
     * @return type
     */
    public function __chigiCaller($method, $args) {
        return call_user_func_array(array(&$this, $method), $args);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function assign($name, $value = '') {
        parent::assign($name, $value);
    }

    private function checkcookie() {
        $addr = $_GET['iframe'];
        if (!CHING::$COOKIE_STATUS) {
            $addr .= (strpos($addr, '?') > 0) ? '&sid=' : '?sid=' . $_GET['sid'];
        }
        redirectHeader($addr);
    }

    private function __chigiCheckURL() {
        if (MODULE_NAME == 'On') {
            //ON万能操作不在URL规范控制内
            return;
        }
        $the_host = $_SERVER['HTTP_HOST']; //取得当前域名
        /* @var $the_url string 判断地址后面的部分，带斜杠开头 */
        $the_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $the_url = strtolower($the_url); //将英文字母转成小写
        if ($the_url == "/index.php") {//判断是不是首页
            $the_url = ""; //如果是首页，赋值为空
        }
        if (
                $the_host != C('CHIGI_HOST')
                || $_SERVER['REQUEST_URI'] != redirect_link(MODULE_NAME . '/' . ACTION_NAME, $_GET, '')
        ) {
            //如果域名不符合规范，则作如下跳转：
            header('HTTP/1.1 301 Moved Permanently'); //发出301头部
            redirectHeader(MODULE_NAME . '/' . ACTION_NAME, $_GET);
        }
    }

}

?>