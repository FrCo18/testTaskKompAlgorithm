<?php
class BrandCategoryImagesDescriptionStock{
    public $Brand = "";
    public $Category =[];
    public $ImagesSrc = [];
    public $Discription="";
    public $Stock = "";
    function __construct($srcTovar){//$srcTovar(Ссылка на страницу с товаром)
        if($curl=curl_init()){
            curl_setopt($curl, CURLOPT_URL,"$srcTovar");
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_TIMEOUT,30);

            //Наличие
            $htmlConstr = curl_exec($curl);
            $StockCheck = mb_substr($htmlConstr, mb_strpos($htmlConstr,'<meta itemprop="availability"'));
            $StockCheck = mb_substr($StockCheck,0,mb_strrpos($StockCheck,'<span class="link-grey underline">'));
            $StockCheck = trim(strip_tags($StockCheck));
            if($StockCheck=="Currently unavailable. More stock on order"){
                $this->Stock= "OutOfStock";
            }
            else{
                $this->Stock= "InStock";
            }

            //Описание товара
            $Discription = mb_substr($htmlConstr, mb_strpos($htmlConstr,'class="product-tagline"'));
            $Discription = mb_substr($Discription,0,mb_strrpos($Discription,'class="text-right link-red"'));
            $Discription=strip_tags($Discription);
            $this->Discription = trim(str_replace('class="product-tagline">','',$Discription));
        
            //Категории
            $Category = mb_substr($htmlConstr, mb_strpos($htmlConstr,'Related Categories:'));
            $Category = mb_substr($Category,0,mb_strrpos($Category,'<div id="product-details-attributes">'));
            $Category=strip_tags($Category);
            $Category=trim(str_replace('Related Categories:','',$Category));
            $this->Category=explode(':',$Category);
        
            //Бренд
            $Brand = mb_substr($htmlConstr, mb_strpos($htmlConstr,'<td class="product-details-attribute-item-title">Primary Brand'));
            $Brand = mb_substr($Brand,0,mb_strpos($Brand,'<td></td>'));
            $Brand=strip_tags($Brand);
            $this->Brand =trim(str_replace('Primary Brand','',$Brand));
            
            //Главное изображение с страницы товара
            $mainImage  = mb_substr($htmlConstr, mb_strpos($htmlConstr,'<img itemprop="image" id="product-primary-image" src="'));
            $mainImage  = mb_substr($mainImage ,0,mb_strpos($mainImage ,'<div class="product-layout-right">'));
            $mainImage  = mb_substr($mainImage, mb_strpos($mainImage,'src="'));
            $mainImage  = mb_substr($mainImage ,0,mb_strpos($mainImage ,'" alt'));
            $mainImage ="https:".trim(str_replace('src="','',$mainImage));
            $nextSmallImagesInArr[]=$mainImage;
        
            //Оставшиеся изображение с страницы товара вытащенные из <ul>
            $nextSmallImages  = mb_substr($htmlConstr, mb_strpos($htmlConstr,'<ul class="overview"'));
            unset($htmlConstr);
            $nextSmallImages  = mb_substr($nextSmallImages ,0,mb_strpos($nextSmallImages ,'<div class="product-right-bottom">'));
            while(mb_strpos($nextSmallImages,'src="')!==false){
                $nextSmallImages1 =  $nextSmallImages  = mb_substr($nextSmallImages, mb_strpos($nextSmallImages,'src="'));
                $nextSmallImages1  = mb_substr($nextSmallImages1 ,0,mb_strpos($nextSmallImages1 ,'" alt="'));
                $fullSrcImage="https:".trim(str_replace('src="','',$nextSmallImages1));
                $nextSmallImagesInArr[] = $fullSrcImage;
                $nextSmallImages=str_replace($nextSmallImages1,'',$nextSmallImages);
            }
            $this->ImagesSrc=$nextSmallImagesInArr;
        }
        else{
            echo "curl_err";
        }
    }
}

//Парметры: $searchTovar(строка в поиске по которой ищем товары) $page(Номер страницы с которой берутся товары(на данном сайте я не нашёл как через ajax выследить прокрутку))
//$print(Параметр отвечает за печатание массива на экран)
function parseGet($searchTovar='game',$page=1, $print="noprint"){
    if($curl=curl_init()){
        $jsonFullParsArray=[];//Будущий массив для единичного фала со всеми товарами
        require_once 'configJson.php';
        curl_setopt($curl, CURLOPT_URL,"https://www.ssww.com/search/page.php?q=$searchTovar&scope=narrow&sort=best&page=$page");
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_TIMEOUT,30);
        $html = curl_exec($curl);
        curl_close($curl);
        preg_match_all('|class="product-box.*?</div></div>|', $html,$arrTovarItems);

        foreach($arrTovarItems[0] as $keyTovarItem=>$valueTovarItem){

            //Название товара
            preg_match('|alt=".*?"|',$valueTovarItem,$nameTovar);
            if(isset($nameTovar[0])){
                $nameTovar=explode('"',$nameTovar[0])[1];
                $nameTovar = str_replace('"','',$nameTovar);
                $nameTovar = trim($nameTovar);
    
                //sku
                preg_match('|<div class="product-box-line product-box-.*?</div>|', $valueTovarItem,$sku);
                $sku = str_replace("</div>","",$sku);
                $sku = explode(">",$sku[0])[1];
                $sku= trim($sku);
    
                //Обычная цена
                preg_match('|<div class="product-box-price-list">.*?</div>|', $valueTovarItem,$priceDefaultCheck1);
                preg_match('|<div class="product-box-price-actual">.*?</div>|', $valueTovarItem,$priceDiscount);
                preg_match('|As Low as <span>.*?</span>|', $valueTovarItem,$priceDefault);
                if(isset($priceDefault[0])){
                        $priceDefault = str_replace('As Low as','',$priceDefault);
                        $priceDefault= trim(strip_tags($priceDefault[0]));
                        $priceDiscount="";
                        
                }elseif (isset($priceDefaultCheck1[0])&&isset($priceDiscount[0])) {

                    $priceDefaultCheck1=str_replace('</div>','',$priceDefaultCheck1);
                    $priceDefault=trim(explode('>',$priceDefaultCheck1[0])[1]);

                    //Цена со скидкой
                    $priceDiscount = str_replace('</div>','',$priceDiscount);
                    $priceDiscount = explode(">",$priceDiscount[0])[1];
                    $priceDiscount= trim($priceDiscount);
                }
                elseif (!isset($priceDefaultCheck1[0])&&isset($priceDiscount[0])) {
                    $priceDiscount = str_replace('</div>','',$priceDiscount);
                    $priceDiscount = explode(">",$priceDiscount[0])[1];
                    $priceDefault= trim($priceDiscount);
                    $priceDiscount="";
                }
    
                preg_match('|href="/item.*?"|', $valueTovarItem,$tovarSrc);
                $tovarSrc=explode('"',$tovarSrc[0])[1];
                $tovarSrc = str_replace('"','',$tovarSrc);
                $tovarSrc = "https://www.ssww.com".trim($tovarSrc);

                $AdditionalFeature = new BrandCategoryImagesDescriptionStock($tovarSrc);//Получение доп. информации о товаре со страницы самого товара путём конструтора
                $configFromParse['name'] =$nameTovar;
                $configFromParse['price_default']=$priceDefault;
                $configFromParse['price_discount']=$priceDiscount;
                $configFromParse['sku']=$sku;

                $configFromParse['category'] = $AdditionalFeature->Category;
                $configFromParse['image_src']=$AdditionalFeature->ImagesSrc;
                $configFromParse['discription']=$AdditionalFeature->Discription;
                $configFromParse['stock']=$AdditionalFeature->Stock;
                $configFromParse['brand']=$AdditionalFeature->Brand;

                $jsonFullParsArray[] = $configFromParse;//Наполнение массива для файла json со всеми товарами

                //Наплнение файлов по каждому товару
                $configFromParseJson =  json_encode($configFromParse);
                file_put_contents("json/parseEnd$keyTovarItem.json", $configFromParseJson);

                //Вывод массива
                    if($print=="print"){
                        echo "<br>[<br>";
                        foreach($configFromParse as $keyConfig=>$valueConfig){
                            if(is_array($valueConfig)){
                                echo "$keyConfig => ";
                                print_r($valueConfig);
                                echo "<br><br>";
                            }else{
                                echo $keyConfig . " => ". $valueConfig."<br><br>";
                            }
                        }
                    echo "]<br><br>";
                    }
                }
            }
            $jsonFullParsArray = json_encode($jsonFullParsArray);
            file_put_contents("parseEndSolo.json", $jsonFullParsArray);
        }
    else{
        echo "curl_err";
    }
    //Заполнение одного файла со всеми товарами сразу
}
