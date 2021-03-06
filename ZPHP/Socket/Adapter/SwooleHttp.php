<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需扩展地址：https://github.com/matyhtf/swoole
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer,
    ZPHP\Socket\ICallback,
    ZPHP\Socket\Callback\SwooleHttp as httpClient,
    ZPHP\Socket\Callback\SwooleWebSocket as wsClient;

class SwooleHttp implements IServer
{
    private $client;
    private $config;
    private $serv;

    public function __construct(array $config)
    {
        if(!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $this->config = $config;
        $this->serv = new \swoole_http_server($config['host'], $config['port'], $config['work_mode']);
        $this->serv->set($config);
        $this->client = new wsClient();
        $this->client->setServer($this->serv);
    }

    public function run()
    {
        $this->serv->on('Start', array($this->client, 'onStart'));
        $this->serv->on('Connect', array($this->client, 'onConnect'));
        $this->serv->on('Request', array($this->client, 'onRequest'));
        $this->serv->on('Close', array($this->client, 'onClose'));
        $this->serv->on('Shutdown', array($this->client, 'onShutdown'));
        $handlerArray = array(
            'onTimer', 
            'onWorkerStart', 
            'onWorkerStop', 
            'onWorkerError',
            'onTask',
            'onFinish',
            'onWorkerError',
            'onManagerStart',
            'onManagerStop',
            'onPipeMessage',
            'onOpen',
            'onMessage',
            'onHandShake',
        );
        foreach($handlerArray as $handler) {
            if(\method_exists($this->client, $handler)) {
                $this->serv->on(\str_replace('on', '', $handler), array($this->client, $handler));
            }
        }
        $this->serv->setGlobal(HTTP_GLOBAL_ALL, HTTP_GLOBAL_GET|HTTP_GLOBAL_POST);
        $this->serv->start();
    }
}
