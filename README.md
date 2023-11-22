<p align="center"><a href="https://www.flipflow.io/" target="_blank"><img src="https://images.crunchbase.com/image/upload/c_lpad,f_auto,q_auto:eco,dpr_1/xpprgcqqpjmeg3fjojvg" width="400" alt="FlipFlow Logo"></a></p>

# PRUEBA TÉCNICA FLIPFLOW

En este README se adjunta todos los conocimientos más relevantes de la implementación de la prueba.

## Set-up del proyecto

Se han utilizado las siguientes versiones en la prueba:
- **PHP: 8.2**
- **Laravel: 10.10**
- **Composer: 2.6.5**

Lo primero que se ha realizado ha sido la creación del proyecto:
```
composer create-project laravel/laravel FlipFlow_Coding_Interview
```

## Migración y modelo

Al tratarse de una prueba sencilla, solo se ha necesitado la creación de una migración (tabla) con su modelo Product asociado (*app/Models*). Para dicha creación se ha hecho uso del siguiente comando:

```
php artisan make:model Product -m
```

El flag **-m** lo ponemos para que nos cree su migración asociada y así nos ahorramos su comando de creación.

La migración (*database/migrations*) quedaría de la siguiente forma:

```js
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->string('image_url');
            $table->string('url');
            $table->timestamps();
        });
    }
```

No se han implementado seeders ya que la tabla se rellenará con el scraping de productos.

El campo id y los timestamps suelen ser recomendables en las tablas aunque en este caso era indiferente.

Para ejecutar la migración ejecutamos el siguiente comando de artisan:
```
php artisan migrate
```

## Base de datos Sqlite

Debido a la simplicidad de nuestra prueba simplemente con añadir la siguiente configuración en el archivo .env es suficiente:

```js
DB_CONNECTION=sqlite
#DB_HOST=127.0.0.1
#DB_PORT=3306
#DB_DATABASE=
#DB_USERNAME=root
#DB_PASSWORD=
```
Una vez ejecutemos las migraciones nos pedirá que si queremos crear un archivo **database.sqlite** (*/database*), a lo que decimos yes, si lo visionamos tendremos lo siguiente:

![Tabla](/images/Migracion.png)


## Comandos personalizados

Se pedía la creación de dos comandos que lancen las tareas de visionado e inserción de productos.

Para la creación de los comandos se ha utilizado el siguiente comando de artisan:

```
php artisan make:command <Save,Show>ProductListCommand
```

Una vez creados (*app/Console/Commands*), se ha configurado el parámetro url que se necesita para realizar el scraping correspondiente:

```js
protected $signature = 'save-product-list {--url=}';
protected $signature = 'show-product-list {--url=}';
```

La implementación de la lógica de los comandos se ha realizado en sus métodos handle, los cuales son llamados cuando se ejecuta el comando correspondiente:

```js
    //SaveProductListCommand
    public function handle(ScraperProductsService $scraper)
    {
        $url = $this->option('url');
        
        if($url){
                $result = $scraper->getProducts($url, 'save');
                $result === true ? $this->info('Products stored correctly') : $this->error('Products could not be stored');
        }
        else{
            $this->error('You must provide the --url param');
        }
    }

    //ShowProductListCommand
    public function handle(ScraperProductsService $scraper)
    {
        $url = $this->option('url');
        
        if($url){
            $result = $scraper->getProducts($url, 'show');
            $result === true ? $this->info('Products showed correctly') : $this->error('Products could not be showed');
        }
        else{
            $this->error('You must provide the --url param');
        }
    }
```

Es interesante mencionar que Laravel realiza la inyección automática del ScraperProductsService al método handle cuando se ejecuta el comando correspondiente. A continuación se detallará más en profundidad de la lógica de este servicio.

## Servicios 

En cuanto a los servicios, en primera instancia se había realizado todo el core de la lógica en el mismo servicio *ScraperProductsService*, luego se hizo una refactorización para las tareas de inserción y mostrado de productos. 

Esto también se ha hecho debido a que para ambos comandos se pedía realizar la tarea de scraping, por lo que era recomendable hacer un extract method y no tener código repetido, ya que la única diferencia es que con los datos scrapeados se realiza o una inserción de productos o un mostrado de los mismos por lo que hay mucho código común para ambos comandos.

Se pensó en realizar una comprobación cuando se ejecutara el show-product-list, de manera que si había contenido en la base de datos, se mostraba, y si no había se realizaba la tarea de scraping, de esta forma te ahorras una petición HTTP.

Finalmente no se ha hecho de esa manera ya que en la base de datos pueden haber datos desactualizados que no se correspondan con el contenido actual de la página (en nuestro caso la de carrefour). Por lo que siempre se realiza el scraping de productos para obtener la información más actualizada posible.

El proceso de implementación y refactorización de los servicios (*app/Services*) se puede ver en los commits.

#### ScraperProductsService

```js
public function getProducts($url, $action): bool
    {
        try{
            $deliveryAddressDetails = $this->getDeliveryAddressDetails();
            $browser = new HttpBrowser(HttpClient::create());
            $browser->request('GET', $url);

            $newDriveCookie = new Cookie(
                'salepoint',
                $deliveryAddressDetails->sale_point_id . '|' . $deliveryAddressDetails->store_id . '||DRIVE|1',
                time() + 3600,
                '/',
                'carrefour.es'
            );

            $browser->getCookieJar()->set($newDriveCookie);
            $browser->request('GET', $url);

            $response = $browser->getResponse();

            if($response->getStatusCode() === 200){
                $body = $response->getContent();
    
                $crawler = new Crawler($body);
                $basePath = $crawler->filter('base')->attr('href');
                $productNodes = $crawler->filter('ul.product-card-list__list > li')->slice(0,5);
                $products = [];

                $productNodes->each(function (Crawler $productNode) use ($basePath, &$products){
                    $product = new Product();
                    $product->name = $productNode->filter('.product-card__title-link')->text();
                    $product->price = $productNode->filter('.product-card__parent')->attr('app_price');
                    $product->image_url = $productNode->filter('.product-card__media-link > img')->attr('src');
                    $product->url = $basePath . $productNode->filter('.product-card__media > a')->attr('href');
                    $products[] = $product->toArray();
                });
                
                if(count($products) > 0){
                    if($action === 'save'){
                        $this->productService->saveProducts($products);
                        return true;
                    } elseif ($action === 'show'){
                        $this->productService->showProducts($products);
                        return true;
                    }
                }
            }
            return false;
        } catch (Exception $e){
            echo 'Caught exception: ' . $e->getMessage() . PHP_EOL;
            return false;
        }

    }
```

Destacar que para el precio del producto se ha utilizado el atributo *app_price* ya que había ocasiones que si había una rebaja en el precio del producto no te servía con la clase *product-card__price* ya que mostraba el precio antes de la rebaja, app_price siempre va a contener el precio actual del producto.

Por lo demás se ha realizado un scraping sencillo analizando el listado de productos, se han recogido los 5 primeros como se pedía en el enunciado.

Se recogen los primeros 5 elementos **li** del listado total de productos y luego se recorre cada uno de ellos recogiendo la información relevante que necesitamos.

Se ha utilizado las siguientes librerías para realizar este proceso:

```js
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
```

Son librerías sencillas para el objetivo que esta prueba persigue.

Para la url del producto se ha concatenado la url base de la página (*https://www.carrefour.es*) ya que el enlace (*product-card__media > a*) solo contiene el endpoint, no la URL completa.

#### Modificación de la dirección de entrega

En primer lugar se realizo un análisis por si era necesario hacer uso de algún tipo de webdriver que simulara la interacción del usuario con la interfaz web, para realizar los pasos necesarios para modificar la dirección de entrega.

Analizando el codigo HTML intenté comprender de donde sacaba la dirección de entrega por defecto o la que decidas configurar, hasta que llegue a la variable ```window.__INITIAL_STATE__```:

```js
                "config": {
                    "allowBots": "true",
                    "showTradeBanner": true,
                    "baseUrl": "https:\u002F\u002Fwww.carrefour.es",
                    "analyzeBundle": false,
                    "maxSelectedFacets": 5,
                    "useCollageCarts": true,
                    "ssrHeaders": {
                        "profile_id": "",
                        "session_id": "2YVNespdFaJ7n51aw4HsIO1s71N",
                        "jsessionid": "",
                        "from_app": "false",
                        "display_cookie_banner": "true",
                        "cookie_banner_version": "3",
                        "c4-canary-group": "one_cart_food",
                        "cookie": "OptanonAlertBoxClosed=2023-11-17T22:29:47.585Z; _gcl_au=1.1.459275023.1700260188; OneTrustGroupsConsent-ES=,C0097,C0001,C0022,C0007,C0166,C0096,C0021,C0052,C0063,C0174,C0081,C0101,C0051,C0023,C0025,C0032,C0033,C0036,C0038,C0039,C0041,C0056,C0082,C0128,C0135,C0005,C0180,C0084,C0167,C0004,; __rtbh.uid=%7B%22eventType%22%3A%22uid%22%2C%22id%22%3A%22unknown%22%7D; __rtbh.lid=%7B%22eventType%22%3A%22lid%22%2C%22id%22%3A%22HjCPCOTEnBtKI1NB7jNt%22%7D; _fbp=fb.1.1700260188276.1642428786; Wizard=true; _cs_c=1; _tt_enable_cookie=1; _ttp=FiGLAOj-TP7Kd3iNrAOlimt_tGC; _pin_unauth=dWlkPVltTmtZemMzWW1NdE9EQXpNeTAwTkRJekxUbGlZVEF0TlRNd05qZGlOakE0T0dSaA; userPrefLanguage=es_ES; PROFILE_ID=5231418282; _gid=GA1.2.1647486398.1700524482; salepoint=005212|4700003||DRIVE|1; __gads=ID=261512b11e15cb01:T=1700260194:RT=1700598667:S=ALNI_MYVtVcmTHQG-KtFMCDrP5fKQjXEqg; __gpi=UID=00000cdc5871aa02:T=1700260194:RT=1700598667:S=ALNI_MbAlbJSKrDMgrxT402bqbSRXnQrug; _ga_KPXW54NX57=GS1.1.1700598665.19.1.1700598693.32.0.0; _ga=GA1.2.559407055.1700260186; _uetsid=2cd037f0880011ee9532714423efece0; _uetvid=d0c43920859811eebbdb2b4faa48a400; _ga_L8PXGMBPF5=GS1.2.1700598665.13.0.1700598694.0.0.0; OptanonConsent=isGpcEnabled=0&datestamp=Tue+Nov+21+2023+21%3A31%3A34+GMT%2B0100+(hora+est%C3%A1ndar+de+Europa+central)&version=202302.1.0&isIABGlobal=false&hosts=&landingPath=NotLandingPage&groups=C0097%3A1%2CC0001%3A1%2CC0022%3A1%2CC0007%3A1%2CC0166%3A1%2CC0096%3A1%2CC0021%3A1%2CC0052%3A1%2CC0063%3A1%2CC0174%3A1%2CC0081%3A1%2CC0101%3A1%2CC0051%3A1%2CC0023%3A1%2CC0025%3A1%2CC0032%3A1%2CC0033%3A1%2CC0036%3A1%2CC0038%3A1%2CC0039%3A1%2CC0041%3A1%2CC0056%3A1%2CC0082%3A1%2CC0128%3A1%2CC0135%3A1%2CC0005%3A1%2CC0180%3A1%2CC0084%3A1%2CC0167%3A1%2CC0004%3A1&geolocation=%3B&AwaitingReconsent=false; cto_bundle=9LMZ1182WmU0anpvNUN3WlJ2RTZYd3FFaHZPYWpiWlNqMmtjYlZTTlhBUHhJekF4Sk5ZMktlemo2VWUlMkZ1RW5ua000QlNGRzNOa0p4QjVYRjlQMVpJRkgxQ2NFMnBSTTAlMkJJS3hJYUQ0cGolMkZyWnU5QlZCbDElMkJtYlA5NmtZY1Q4dXI5anZlbjFOemprYWN1Ym4lMkJvNldEczMlMkZUcVphRU8zV244YkdtaEZOUWVubDBZYlElM0Q; _cs_id=36f4a452-1cbc-a485-ff3c-cbaaaae38714.1700260190.19.1700606879.1700606879.1.1734424190642; _cs_s=1.0.0.1700608679197; session_id=2YVNespdFaJ7n51aw4HsIO1s71N; JSESSIONID=; JSESSIONID_ALI11=",
                        "sale_point": "004320",
                        "postal_code": "28020",
                        "delivery_type": "A_DOMICILIO"
                    },
```

Aquí es donde vi que había campos relativos a la dirección de entrega como *sale_point*, *postal_code* o *delivery_type* y pensé la manera de modificar esto por código para que realizara dicha configuración con la dirección que queríamos.

Me di cuenta que cuando modificabas la dirección de entrega, estos campos se actualizaban a los valores de la dirección que tu indicabas, dichos datos vi de donde salían al analizar la petición que se realiza para rellenar el listado con el selector de todas las direcciones disponibles:

![Tabla](/images/ListadoDirecciones.png) 

Estos datos se cargaban gracias a la respuesta de esta petición:

![Tabla](/images/LlamadaDirecciones.png) 

La cual devuelve un json con los siguientes datos(entre otros muchos):

```js
{
    "groups": [
        {
            "name": "1. Madrid",
            "sale_points": [
                [...]
                {
                    "city": "Madrid",
                    "country": "Spain",
                    "fictional_postal_code": "99152",
                    "geocode": "40.394,-3.769",
                    "group": "1. Madrid",
                    "latitude": "40.394",
                    "longitude": "-3.769",
                    "name": "Drive Peatón MK Aluche",
                    "postal_code": "28024",
                    "province": "Madrid",
                    "sale_point_id": "005214",
                    "store_id": "4700007",
                    "street_name": "Padre Piquer",
                    "street_number": "s/n",
                    "street_type": "Avenida"
                },
                {
                    "city": "Madrid",
                    "country": "Spain",
                    "fictional_postal_code": "99150",
                    "geocode": "40.433,-3.704",
                    "group": "1. Madrid",
                    "latitude": "40.433",
                    "longitude": "-3.704",
                    "name": "Drive Peatón MK Quevedo",
                    "postal_code": "28010",
                    "province": "Madrid",
                    "sale_point_id": "005212",
                    "store_id": "4700003",
                    "street_name": "Fuencarral",
                    "street_number": "158",
                    "street_type": "Calle"
                },
                [...]
```

¡Vaya!, parece que aquí están los datos que necesitábamos para modificar esa configuración inicial que veíamos que tenía el HTML de la página. Ahora me faltaba averiguar por donde se le pasaban los datos de la dirección de entrega para que *__INITIAL_STATE__* tuviera los datos correctos de la dirección de entrega seleccionada.

Me di cuenta de que esto se realizaba a través de las cookies de la página:

![Tabla](/images/Cookies.png) 

Así que ya lo tenía claro, debía añadir esa cookie en la llamada con los datos correspondientes a la direccion de **Drive Peatón MK Quevedo**.

Para ello realicé el siguiente método que obtenía los datos relativos a dicha dirección haciendo una peticion al script de drives comentado anteriormente:

```js
    public function getDeliveryAddressDetails(): object
    {
        $deliveryAddress = 'Drive Peatón MK Quevedo';
        $browser = new HttpBrowser(HttpClient::create());
        $browser->request('GET', 'https://www.carrefour.es/cloud-api/salepoints/v1/drives');

        $response = $browser->getResponse();
        $content = $response->getContent();
        $contentJSON = json_decode($content, false);

        $salePoints = array_map(function($group) {
            return $group->sale_points;
        }, $contentJSON->groups);

        $salePoints = array_merge(...$salePoints);

        $driveQuevedo = array_filter($salePoints, function($salePoint) use ($deliveryAddress) {
            return $salePoint->name === $deliveryAddress;
        });

        return array_values($driveQuevedo)[0];
    }
```

A partir del objeto devuelto, en el método **getProducts** ya crea la cookie correspondiente y realiza la llamada HTTP como se puede ver en capturas anteriores de dicho método.

Por último, destacar que se han realizado comprobaciones y tratamiento de excepciones, ya que puede existir el caso en el que se le pase una URL errónea o que la URL exista pero no se corresponda con la que necesitamos para realizar el scraping.

#### ProductService

La lógica de este servicio es muy sencilla ya que simplemente hace el tratamiento con los datos scrapeados:

```js
class ProductService {
    
    public function saveProducts($products): void
    {
        Product::insert($products);
    }

    public function showProducts($products): void
    {
        echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
```

## Testing

Se han realizado tests unitarios (*/tests/Unit*) para todos los métodos implementados.

Para realizar la ejecución de ellos se ejecuta el siguiente comando:
```
php artisan test
```

Se han utilizado mocks para probar la parte de la aplicación que realmente queremos, sin ningún tipo de dependencia:

```js
class ScraperProductsServiceTest extends TestCase
{
    private $url = 'https://www.carrefour.es/supermercado/congelados/cat21449123/c';

    public function testGetProductsSaveAction(): void
    {
        $mockProductService = Mockery::mock(ProductService::class);
        $mockProductService->shouldReceive('saveProducts')->once();

        $this->app->instance(ProductService::class, $mockProductService);
        $scraperProductsService = new ScraperProductsService($mockProductService);
        $result = $scraperProductsService->getProducts($this->url, 'save');
        $this->assertTrue($result);
    }

    public function testGetProductsShowAction(): void
    {
        $mockProductService = Mockery::mock(ProductService::class);
        $mockProductService->shouldReceive('showProducts')->once();

        $this->app->instance(ProductService::class, $mockProductService);
        $scraperProductsService = new ScraperProductsService($mockProductService);
        $result = $scraperProductsService->getProducts($this->url, 'show');
        $this->assertTrue($result);
    }

    /*Resto de tests*/
}
```

## Ejemplos de ejecución

A partir de toda la implementación comentada, se obtienen las salidas esperadas de ambos comandos:

![Tabla](/images/SalidaShowCommand.png) 

![Tabla](/images/SalidaSaveCommand.png)

![Tabla](/images/bdrellena.png) 


## Dockerización de la aplicación

Se ha realizado el empaquetamiento de la aplicación en una imagen docker. 
Para ello se ha utilizado la siguiente configuración (*Dockerfile*):

```
FROM php:8.2-cli

USER root

WORKDIR /var/www

COPY . /var/www/

RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        sqlite3 \
        libsqlite3-dev \
    && docker-php-ext-install zip pdo_sqlite

#Instalacion de composer y de las dependencias necesarias
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-scripts --no-interaction --optimize-autoloader \
    && rm -rf /usr/local/bin/composer

CMD ["php", "artisan"]
```

Se han instalado todas las dependencias necesarias junto con composer.

Gracias a esta configuración podemos realizar la construcción de la imagen:
```
docker build -t flipflow_coding_interview .
```

A partir de ahí podemos ejecutar nuestros comandos de artisan sobre la imagen:

```
docker run flipflow_coding_interview php artisan save-product-list --url=https://www.carrefour.es/supermercado/congelados/cat21449123/c
docker run flipflow_coding_interview php artisan show-product-list --url=https://www.carrefour.es/supermercado/congelados/cat21449123/c
```

Y se observa que se reciben las salidas esperadas:

![Tabla](/images/ShowCommandDocker.png)

![Tabla](/images/SaveCommandDocker.png)

Y si miramos el archivo database.sqlite dentro de la ruta */var/www* dentro del container:

![Tabla](/images/bddocker.png)

Se ha subido la imagen a docker hub para poder hacer un pull de ella y poder usarla. Se han seguido los siguientes pasos:

```
docker login
docker tag flipflow_coding_interview javigl/flipflow_coding_interview:v1
docker push javigl/flipflow_coding_interview:v1
```

Se puede realizar un pull de la misma para poder probar su código.


## Limitaciones de escalabilidad

Por último y como conclusiones finales cabe comentar algunas de las limitaciones de escalabilidad que tendría la aplicación implementada:

1. **Uso de base de datos SQLite**: Para este tipo de aplicación que simplemente tiene una tabla es una buena solución y no necesitamos más, ya que sqlite es muy fácil de usar y a penas necesita configuración alguna. Pero a largo plazo y con un aumento del volumen de datos, SQLite es muy limitado en cuanto a escalabilidad y limitación de almacenamiento y operaciones.

2. **Dependencia de la estructura HTML**: El scraper depende mucho de la estructuctura que actual del sitio web de Carrefour. Aunque he intentado hacerlo lo más genérico posible la dependencia al HTML actual es alta. Se debería intentar implementar una solución con más robustez.

3. **Paginación**: Aunque solo se pedía extraer los 5 primeros productos del listado, si la aplicación escalara y necesitara obtener una mayor cantidad de datos, podría ser interesante implementar un sistema de paginación para un procesamiento de la información más eficiente. Así reduciríamos la carga de datos.

4. **Dockerización**: La configuración realizada para esta prueba (*Dockerfile*), podría tener limitaciones en el caso de que la aplicación escalara y se necesitaran varias instancias de ella.

A pesar de estas limitaciones, se ha intentado implementar la solución más sencilla posible para abordar el problema dado.

