<?
class SL_ChangeInline
{
    private static $dirTempPatch;
    private static $count;
    private static $name;
    private static $arStyle;

    public static function beforeHandler()
    {
        if ((!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) && self::checkAjax() === false && strpos(self::getName(), "bitrix") === false) {
            global $APPLICATION;
            self::$dirTempPatch = "/bitrix/js/slobel.noinline/css/";
            self::$name = self::getName();
            self::saveFile(true);
            $APPLICATION->SetAdditionalCSS(self::$dirTempPatch . self::$name . ".css");
        }
    }

    public static function afterHandler(&$content)
    {
        if ((!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) && self::checkAjax() === false && strpos(self::getName(), "bitrix") === false) {
             self::$count = 0;
             $content = preg_replace_callback('/\<style+.*?\>(.*?)\<\/style\>/is',"self::findStyleTag", $content);
             $content = preg_replace_callback('/<([a-z][a-z0-9]*)[^>](.*?)style="(.*?)"(.*?)(>|\/>)/i',"self::findInlineStyle", $content);
             self::saveFile();
        }
    }

    private function findStyleTag($matches)
    {
        if (strpos($matches[1], "bx-") === false) {
            self::$arStyle .= $matches[1] . "\n";
            return "";
        } else {
            return $matches[0];
        }
    }
    
    private function findInlineStyle($matches)
    {
            $attrs = array();
            $separator = " ";
            $myClass = "sl-".self::$name . "-" . self::$count;

            if (strpos($matches[3], ";") === false) {
                $matches[3] = $matches[3] . ";";
            }

            self::$arStyle .= 'body ' . $matches[1] . '.' . $myClass . "{" . $matches[3] . "}\n";

            preg_match_all("/(checked|selected|disabled)/", $matches[2] . $matches[4], $oneAttr);

            foreach ($oneAttr[1] as $onePair) {
                $attrs[trim($onePair)] = trim($onePair);
            }

            preg_match_all("/\b(\w+\s*=\s*([\"'])[^\\2]+?\\2)/", $matches[2] . $matches[4], $pairs);
            foreach ($pairs[0] as $pair) {
                $atr = array_map("self::trimQuotes", preg_split("/\s*=\s*/", $pair));
                $attrs[$atr[0]] = $atr[1];
            }

            if (array_key_exists("class", $attrs) === false) {
                $separator=$attrs["class"] = "";
            }

            foreach ($attrs as $key => $value) {
                if($key == "class") {
                    $value .= $separator . $myClass;
                }
                $attr .= $key . "=\"" . $value . "\" ";
            }

        self::$count++;
        return '<' . $matches[1] . " " . $attr.$matches[5];
    }

    private function trimQuotes($data)
    {
        $data = preg_replace("/(^['\"]|['\"]$)/", "", $data);
        return $data;
    }

    private function getName()
    {
        $myAddress = explode('?', $_SERVER['REQUEST_URI']);
        if ($myAddress[0] == '/') {
            return 'home';
        } else {
            return preg_replace("|[^a-z]*|i", "", $myAddress[0]);
        }
    }

    private function saveFile($create = false)
    {
        if (is_bool($create) === true && $create === true) {
            if (!file_exists($_SERVER["DOCUMENT_ROOT"] . self::$dirTempPatch . self::$name . ".css")) {
                CheckDirPath($_SERVER["DOCUMENT_ROOT"] . self::$dirTempPatch);
                file_put_contents($_SERVER["DOCUMENT_ROOT"] . self::$dirTempPatch . self::$name . ".css", "/*sl-my-stile*/");
                return true;
            }
        } else {
                file_put_contents($_SERVER["DOCUMENT_ROOT"] . self::$dirTempPatch . self::$name . ".css", self::$arStyle);
                self::updateCacheFile(self::$arStyle);
                return true;
        }
    }

    private function setStyle($matches)
    {
        return '<head>' . $matches[1] . "\n" . '<link href="' . self::$dirTempPatch . self::$name . '.css" type="text/css" rel="stylesheet"></head>';
    }

    private function checkAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function updateCacheFile($style)
    {
        $cachFile = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/cache/css/" . SITE_ID . "/slobel/kernel_slobel.noinline/kernel_slobel.noinline.css";
        $putFile = preg_replace_callback('/(\/\*sl-my-stile\*\/)/is', 'self::replaceCacheString', file_get_contents($cachFile));
        file_put_contents($cachFile, $putFile);
    }

    private function replaceCacheString($matches)
    {
        return self::$arStyle;
    }
}
?>