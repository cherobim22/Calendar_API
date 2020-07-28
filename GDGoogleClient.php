<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



require_once "./vendor/autoload.php";

class GDGoogleCLient{
    /**
     * Instancia uma nova instancia da abstracao do Google
     * 
     * @return GDGoogleCLient
     */
    public function __construct(){
        $this->client = new Google_Client;
        $this->client->setAuthConfig('credentials.json');
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        return $this;
    }

    /**
     * Funcao para buscar os eventos
     *
     * @param string $calendar_id Se passar como null vai ser da agenda primaria
     * @return array Um array de eventos
     */

    public function getEvents(){
        if(!$this->is_authenticated){
            throw new Exception("Você precisa autenticar primeiro", 1);            
        }    
        if(!$this->calendar){
            $this->setCalendar($this->calendar_id);
        }
        
        $optParams = array(
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),//0000-01-01T20:38:58+02:00
        );
        $results = $this->calendar->events->listEvents($this->calendar_id, $optParams);
        return $results->getItems();
    }

    /**
     * Funcao para atualizar um evento
     * 
     * @return Obj objeto do evento atualizado
     */
    public function updateEvents($body){
        if(!$this->is_authenticated){
            throw new Exception("Você precisa autenticar primeiro", 1);            
        } 
        $this->id_event = $body['event_id'];

        if(!$this->calendar){
            $this->setCalendar($this->calendar_id);
        }
        //$event->setSummary($body['summary']);
        //$event->setDescription($body['description']);
        $optParams = new Google_Service_Calendar_Event(array(
            'summary' => $body['summary'],
          //'description' =>  $body['description'],
            'start' => array('dateTime' => $body['start_datetime'], 'timeZone' => 'America/Sao_Paulo'), 
            'end' => array('dateTime' => $body['end_datetime'], 'timeZone' => 'America/Sao_Paulo'),
            'attendees' => array(
                array('email' => $body['participante_1']),
                array('email' => $body['participante_2']),
              ),
        ));
        
        $updateEvent = $this->calendar->events->update($this->calendar_id, $this->id_event, $optParams);
        return $updateEvent;
    }

    /**
     * Funcao para deletar um evento
     * 
     * @return Msg deletado
     */
    public function deleteEvents($body){
        $this->id_event = $body['event_id'];
        if(!$this->calendar){
            $this->setCalendar($this->calendar_id);
        }
        try{
             $deletEvent = $this->calendar->events->delete($this->calendar_id, $this->id_event);
             return "deletado";
        }catch (Exception $e) {
            echo 'Evento não encontrado: ',  $e->getMessage(), "\n";
        }
    }
    
    /**
     * Funcao para buscar a url em que o cliente vai autenticar
     * 
     * @return string Url em que o cliente tem que acessar
     */
    public function getAuthUrl(){
        $this->client->setRedirectUri($this->redirect_uri);
        $this->client->setApprovalPrompt('force');
        $this->client->setIncludeGrantedScopes(true);
        return $this->client->createAuthUrl();
    }

    /**
     * Funcao para setar o code
     */
    public function setCode($code){
        $this->code = $code;
    }

    /**
     * Seta os atributos do token, expires, token, refresh_token,scope
     */
    public function setToken($arr_token){
        $this->token = $arr_token;
        $this->client->setAccessToken($this->token);
        if($this->client->isAccessTokenExpired($this->token)){
            $client->refreshToken($this->token['refresh_token']);
            $this->token = $client->getAccessToken();
            $this->client->setAccessToken($this->token['access_token']);
            //atualizaria no banco
        }
        $this->is_authenticated = true;
    }

    /**
     * Funcao para buscar o token com o code
     * @return Array Array com token, expires, escopo, refresh token
     */
    public function getToken(){
        $this->client->setRedirectUri($this->redirect_uri);
        $this->client->setIncludeGrantedScopes(true);
        $this->client->authenticate($this->code);
        return $this->client->getAccessToken();
    }
    
    /** 
     * 
     * Funcao para criar uma instancia do calendar
     */
    private function setCalendar($calendar_id){
        $this->calendar = new Google_Service_Calendar($this->client);
    }
    
    /**
     * Funcao para setar o id do calendar
     */
    public function setCalendarId($calendar_id){
        $this->calendar_id = $calendar_id;
    }

    private $calendar = null;
    private $is_authenticated = false;
    private $code;
    private $token;
    private $calendar_id = "primary";
    //private $redirect_uri = "https://cherobim.innovaweb.com.br/allan/callback.php";
    private $redirect_uri = "http://localhost:8000/callback.php";
}