<?php

/**
    CREATE TABLE source (
        id int NOT NULL auto_increment,
        name varchar(255),
        PRIMARY KEY(id)
    );
    CREATE TABLE article (
        id int NOT NULL auto_increment,
        source_id int NOT NULL,
        name varchar(255),
        content BLOB,
        PRIMARY KEY(id)
    );

    INSERT INTO source VALUES (1, 'src-1');
    INSERT INTO source VALUES (2, 'src-2');

    INSERT INTO article VALUES (1, 1, 'Article 1', 'Lorem ipsum dolor sit amet 1');
    INSERT INTO article VALUES (2, 2, 'Article 2', 'Lorem ipsum dolor sit amet 2');
    INSERT INTO article VALUES (3, 2, 'Article 3', 'Lorem ipsum dolor sit amet 3');
    INSERT INTO article VALUES (4, 1, 'Article 4', 'Lorem ipsum dolor sit amet 4');
*/

class Source
{
    /**
     * @var
     */
    protected $id;
    /**
     * @var
     */
    protected $name;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }


    public function hydrateFromArray($array)
    {
        $this->id = $array['id'];
        $this->name = $array['name'];
    }
}

/**
 * Class Article
 */
class Article
{
    protected $id;

    protected $name;

    protected $content;

    protected $source;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Article
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     * @return Article
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @param array $array
     */
    public function hydrateFromArray(array $array)
    {
        $this->id = $array['id'] ?: null;
        $this->name = $array['name'];
        $this->content = $array['content'];
        $source = new Source();
        $source->hydrateFromArray(['id' => $array['sourceId'], 'name' => $array['sourceName']]);
        $this->source = $source;
    }

}

/**
 * Class ArticleAgregator
 */
class ArticleAgregator
{
    protected string $query;
    protected array $articles;

    /**
     * ArticleAgregator constructor.
     */
    public function __construct()
    {
        $this->query = 'SELECT a.id, a.name, a.content, s.id as sourceId, s.name as sourceName FROM article a join source s on a.source_id = s.id;';
    }

    /**
     * @param $host
     * @param $user
     * @param $pwd
     * @param $dbName
     */
    public function appendDatabase($host, $user, $pwd, $dbName)
    {
        $connection = mysqli_connect($host, $user, $pwd, $dbName);
        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }

        $result = $connection->query($this->query);
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                $article = new Article();
                $article->hydrateFromArray($row);
                $this->articles[] = $article;
            }
        } else {
            echo "0 results";
        }

        $connection->close();
    }

    /**
     * @param $name
     * @param $url
     */
    public function appendRss($name, $url)
    {
        $xml = simplexml_load_file($url, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($this->checkXmlKeys($xml, 'channel')) {
            $sourceName = $xml->channel->title;
            foreach ($xml->channel->item as $item) {
                $article = new Article();
                $article->setName($item->title);
                $article->setContent($item->link);
                $source = new Source();
                $source->hydrateFromArray(['id' => '', 'name' => $sourceName]);
                $article->setSource($source);

                $this->articles[] = $article;
            }
        }
    }

    /**
     * @return array
     */
    public function getArticles(): array
    {
        return $this->articles;
    }

    /**
     * @param $xml
     * @param $key
     * @return bool
     */
    protected function checkXmlKeys($xml, $key): bool
    {
        return $xml->$key ? true : false;
    }

}

$a = new ArticleAgregator();

/**
 * Récupère les articles de la base de données, avec leur source.
 * host, username, password, database name
 */
$a->appendDatabase('localhost', 'root', 'root', 'alltricks');

/**
 * Récupère les articles d'un flux rss donné
 * source name, feed url
 */
$a->appendRss('Le Monde',    'http://www.lemonde.fr/rss/une.xml');

foreach ($a->getArticles() as $article) {
    if($article instanceof Article) {
        echo sprintf('<h2>%s</h2><em>%s</em><p>%s</p>',
            $article->getName(),
            $article->getSource()->getName(),
            $article->getContent()
        );
    }
}
