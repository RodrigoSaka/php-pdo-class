<?php
/**
 * Classe Generica de PDO (MySQL)
 * Como usar:
 * 1. Carregar esse arquivo require("db.class.php");
 * 2. Instancie: new DB();
 */
class DB{
    # @string, Nome do servidor
    private $hostname;
    # @string, Banco de dados
    private $database;
    # @string, Username
    private $username;
    # @string, Senha
    private $password;
    # @object, Objeto PDO
    private $pdo;
    # @object, Estado do objeto PDO
    private $sQuery;
    # @bool , Conectado/Desconectado
    private $bConnected = false;
    # @array, Parametros da query
    private $parameters;
    /**
     * Construtor padrão
     * Seta os dados para onexão ao banco de dados
     * @param string $database [Nome do banco de dados (Opcional)]
     * @param string $username [Nome do usuário (Opcional)]
     * @param string $password [Senha do usuário (Opcional)]
     */
    public function __construct($database=null,$username=null,$password=null){
        # Setando dados para conexão
        $serverName = $_SERVER['SERVER_NAME'];
        # Validando dados através do dominio 
        # *Valores padrões*
        switch($serverName){
            case 'localhost' || '127.0.0.1':
                $this->hostname = 'l60ger0354.locaweb.com.br';
                $this->database = '';
                $this->username = '';
                $this->password = '';
            break;
            case 'homologa.mokedsystem.com':
                $this->hostname = 'l60ger0354.locaweb.com.br';
                $this->database = 'moked_1';
                $this->username = '';
                $this->password = 'mKON6RLDC95_dv';
            break;
            case 'mokedsystem.com':
                $this->hostname = 'l60ger0354.locaweb.com.br';
                $this->database = 'localhost';
                $this->username = '';
                $this->password = '';
            break;
        }
        # Caso o nome do banco seja definido
        if(!empty($database)){
            #Bancos oficiais
            $this->database = $database;
        }
        # Caso o username seja definido
        if(!empty($username)){
            #Bancos oficiais
            $this->username = $username;
        }
        # Caso o password seja definido
        if(!empty($password)){
            #Bancos oficiais
            $this->password = $password;
        }
        # Inicializando a conexão
        $this->Connect();
        $this->parameters = array();
    }
    
    /**
     * Método para conexão do MySQL
     */
    private function Connect(){
        $dsn = 'mysql:dbname='.$this->database.';host='.$this->hostname;
        # Read settings from INI file, set UTF8
        $this->pdo = new PDO($dsn, $this->username, $this->password, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
        # We can now log any exceptions on Fatal error. 
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        # Disable emulation of prepared statements, use REAL prepared statements instead.
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        # Connection succeeded, set the boolean to true.
        $this->bConnected = true;
    }
    /*
    * Fecha a conexão
    *
    */
    public function CloseConnection(){
        # Set the PDO object to null to close the connection
        # http://www.php.net/manual/en/pdo.connections.php
        $this->pdo = null;
    }
        
    /**
    * Todos os métodos necessários para executar.
    * 
    * 1. Se não estiver conectado, é conectado ao banco de dados.
    * 2. Prepara a Query.
    * 3. Parametriza Query.
    * 4. Executa Query.   
    * 6. Reseta o Parameters.
    */
    private function Init($query,$parameters = ""){
        # Conecta ao banco de dados
        if(!$this->bConnected){$this->Connect();}
        # Parametriza Query
        $this->bindMore($parameters);
        # Bind da paremetrização
        if(!empty($this->parameters)){
            foreach($this->parameters as $param){
                $parameters = explode("\x7F",$param);
                # Não esta usando o bindValue ou bindParam devido as restrições e problemas referente a passagem de parametros.
                $query = preg_replace("/{$parameters[0]}(?!\w)/", $parameters[1], $query);
            }       
        }
        # Prepara a Query
        $this->sQuery = $this->pdo->prepare($query);
        # Executa Query.  
        $this->success = $this->sQuery->execute();
        # Reseta o Parameters
        $this->parameters = array();
    }
        
    /**
    * @void 
    *
    * Adiciona parametros
    * @param string $para  
    * @param string $value 
    */  
    public function bind($para, $value){
        $this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . $value;
    }
    /**
    * @void
    * 
    * Adiciona mais parametros
    * @param array $parray
    */  
    public function bindMore($parray){
        if(empty($this->parameters) && is_array($parray)) {
            $columns = array_keys($parray);
            foreach($columns as $i => &$column) {
                $this->bind($column, $parray[$column]);
            }
        }
    }
    /**
    * Se na query conter SELECT ou SHOW retorna array com os resultados
    * Se na query conter DELETE,INSERT ou UPDATE retorna a quantidade de linhas afetadas
    * Ex1: 
    *     $persons = $db->query("SELECT * FROM users");
    * Ex2:
    *     $db->bind("id","1");
    *     $db->bind("firstname","John");
    *     $person = db->query("SELECT * FROM Persons WHERE firstname = :firstname AND id = :id");
    * Ex3:
    *     $db->bindMore(array("firstname"=>"John","id"=>"1"));
    *     $person = $db->query("SELECT * FROM Persons WHERE firstname = :firstname AND id = :id"));
    * Ex4:
    *     $person = db->query("SELECT * FROM Persons WHERE firstname = :firstname AND id = :id",array("firstname"=>"John","id"=>"1"));
    * 
    * DELETE/UPDATE/INSERT
    * Ex:
    *     $delete = $db->query("DELETE FROM Persons WHERE Id = :id", array("id"=>"1"));
    *     $update = $db->query("UPDATE Persons SET firstname = :f WHERE Id = :id", array("f"=>"Jan","id"=>"32"));
    *     $insert = $db->query("INSERT INTO Persons(Firstname,Age) VALUES(:f,:age)", array("f"=>"Vivek","age"=>"20"));
    *
    * Aviso: Ao usar 'where in' não é possivel usar bind
    *     $db->bind('ids',$ids);
    *     $db->query('select * from capiroto where id in (:ids)');//Não funciona (Precisa usar com parametrização)
    * Use dessa forma:
    *     $db->query("select * from capiroto where id in ({join(',',$ids)})");
    * @param  string $query
    * @param  array  $params
    * @param  int    $fetchmode
    * @return mixed
    */          
    public function query($query,$params = null, $fetchmode = PDO::FETCH_ASSOC){
        # Elimina espaços desnecessários
        $query = trim($query);
        $this->Init($query,$params);
        $rawStatement = explode(" ", $query);
        $statement = strtolower(trim($rawStatement[0]));
        if($statement === 'select' || $statement === 'show') {
            return $this->sQuery->fetchAll($fetchmode);
        }
        elseif( $statement === 'insert' ||  $statement === 'update' || $statement === 'delete' ) {
            return $this->sQuery->rowCount();   
        }   
        else{
            return NULL;
        }
    }
    /**
    *  Retorna o ultimo ID inserido.
    *  @return string
    */   
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }   
    /**
    * Retorna apenas uma coluna informada
    * Ex1:
    *     $db->column("SELECT Firstname FROM Persons");
    *
    * @param  string $query
    * @param  array  $params
    * @return array
    */
    public function column($query,$params = null){
        $this->Init($query,$params);
        $Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);     
        $column = null;
        foreach($Columns as $cells) {
            $column[] = $cells[0];
        }
        return $column;
    }   
    /**
    * Retorna apenas uma linha
    * Ex1: 
    *     $ages = $db->row("SELECT * FROM Persons WHERE  id = :id", array("id"=>"1"));
    * Ex2:
    *     $db->bind("id","3");     
    *     $firstname = $db->single("SELECT firstname FROM Persons WHERE id = :id"); 
    *
    * @param  string $query
    * @param  array  $params
    * @param  int    $fetchmode
    * @return array
    */  
    public function row($query,$params = null,$fetchmode = PDO::FETCH_ASSOC){          
        $this->Init($query,$params);
        return $this->sQuery->fetch($fetchmode);            
    }
    /**
    * Retorna apenas um dado
    * Ex1:
    *     $db->bind("id","3");
    *     $firstname = $db->single("SELECT firstname FROM Persons WHERE id = :id");
    *
    * @param  string $query
    * @param  array  $params
    * @return string
    */
    public function single($query,$params = null){
        $this->Init($query,$params);
        return $this->sQuery->fetchColumn();
    }
    /**
     * Inicia transação
     * @return void
     */
    public function start(){
        $this->pdo->beginTransaction();
    }
    /**
     * Efetiva a alteração
     * @return void
     */
    public function commit(){
        $this->pdo->commit();
    }
    /**
     * Restaura dados alterados
     * @return void
     */
    public function rollback(){
        $this->pdo->rollBack();
    }
}