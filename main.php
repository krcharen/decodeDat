<?php

new main();

class main
{
    /**
     * @var int
     */
    private $starttime = 0;

    /**
     * @var
     */
    private $fileDir;

    /**
     * @var
     */
    private $datFiles;

    /**
     * @var
     */
    private $fileSaveDir;

    /**
     * main constructor.
     */
    public function __construct()
    {
        $this->starttime = microtime(true);
        ini_set('memory_limit', '1024M');
        $this->runtime();
        $this->loadfiles();
        $this->convertToImages();
    }

    /**
     * 判断脚本运行模式
     */
    public function runtime()
    {
        if (PHP_SAPI !== 'cli') exit('请在CLI模式下运行该程序！');
    }

    /**
     * 加载指定文件
     */
    public function loadfiles()
    {
        echo '请输入包含微信.dat的文件的【绝对路径】：' . PHP_EOL;
        $fromDir = trim(fgets(STDIN));
        $files = scandir($fromDir);
        foreach ($files as $key => $value) {
            if (in_array($value, ['.', '..']) || pathinfo($value)['extension'] !== 'dat') unset($files[$key]);
        }
        if (count($files) < 1) exit('→ 不正确的文件路径，正在退出……');
        echo '→ 总共找到 ' . count($files) . ' 个dat文件。是否继续？【Y：继续】【N：退出】' . PHP_EOL;
        $isContinue = trim(fgets(STDIN));
        if ($isContinue === 'Y') {
            echo '请输入转换后文件保存路径：' . PHP_EOL;
            $saveDir = trim(fgets(STDIN));
            if (!is_dir($saveDir)) echo @mkdir($saveDir, 0777, true) ? '→ 文件夹创建成功' : exit('→ 文件夹创建失败，正在退出……');
            $this->fileSaveDir = $saveDir;
            $this->datFiles = $files;
            $this->fileDir = $fromDir;
            echo PHP_EOL . "----------------------------------开始转换----------------------------------" . PHP_EOL . PHP_EOL;
        } else {
            exit('→ 请按提示输入指令，Bye!');
        }
    }

    /**
     * 将.dat文件还原成图片
     */
    public function convertToImages()
    {
        $filePath = rtrim(str_replace('\\', '/', $this->fileDir), '/');
        $savePath = rtrim(str_replace('\\', '/', $this->fileSaveDir), '/');
        $number = 0;
        foreach ($this->datFiles as $file) {
            $number++;
            $datFile = file_get_contents($filePath . '/' . $file);
            $strs = str_split(strtoupper(bin2hex($datFile)), 2);
            $keyArr = $this->computeKey($strs[0], $strs[1]);
            $hexStrs = '';
            foreach ($strs as $k => $hex) {
                $hexStr = dechex(hexdec($hex) ^ $keyArr[0]);
                $hexStr = strlen($hexStr) < 2 ? str_pad($hexStr, 2, 0, STR_PAD_LEFT) : $hexStr;
                $hexStrs .= $hexStr;
            }
            $imgData = pack('H*', $hexStrs);
            file_put_contents($savePath . '/' . $file . '.' . $keyArr[1], $imgData, true);
            echo '→ 转换第：' . $number . ' 个' . PHP_EOL;
        }
    }

    /**
     * 计算key值
     *
     * @param $byte01
     * @param $byte02
     * @return array
     */
    public function computeKey(string $byte01, string $byte02)
    {
        //图片十六进制标识码，第一位和第二位。与之异或，获取中间key值。
        $byteCode = [
            'jpg' => ['0xFF', '0xD8'], //255,216
            'gif' => ['0x47', '0x49'], //71,73
            'png' => ['0x89', '0x50'], //137,80
        ];
        foreach ($byteCode as $k => $v) {
            $_00 = hexdec($v[0]) ^ hexdec($byte01);
            $_01 = hexdec($v[1]) ^ hexdec($byte02);
            if ($_00 === $_01) return [$_01, $k];
        }
    }

    /**
     * 计算程序运行时间
     */
    public function __destruct()
    {
        echo PHP_EOL . "----------------------------------转换结束----------------------------------" . PHP_EOL . PHP_EOL;
        echo PHP_EOL . '[本次运行：' . round(microtime(true) - $this->starttime, 3) . ' s]' . PHP_EOL . PHP_EOL;
    }
}