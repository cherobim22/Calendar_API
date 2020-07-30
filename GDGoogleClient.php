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
            'orderBy' => 'startTime' ,
            'singleEvents' => true,
            'timeMin' => date('c'),//0000-01-01T20:38:58+02:00
        );
        $results = $this->calendar->events->listEvents($this->calendar_id, $optParams);
        return $results->getItems();
    }

    /**
     * Funcao para criar um evento
     * Sumario, Descrição, Hora Inicio, Hora Fim, Participantes
     * @return Obj objeto do evento criado
     */
    public function createEvent($body){

        if(!$this->calendar){
            $this->setCalendar($this->calendar_id);
        }
        $event = [
            'start' => array('dateTime' => $body['start_datetime'], 'timeZone' => 'America/Sao_Paulo'), 
            'end' => array('dateTime' => $body['end_datetime'], 'timeZone' => 'America/Sao_Paulo'),
            'attendees' => array(
                array('email' => $body['participante_1']),  
                array('email' => $body['participante_2']),
            ),
        ];
        if(isset($body['description'])){
            $event['description'] = $body['description'];
        }
        if(isset($body['summary'])){
            $event['summary'] = $body['summary'];
        }
        $optParams = new Google_Service_Calendar_Event($event);
        $creat_event = $this->calendar->events->insert($this->calendar_id, $optParams);
        return $creat_event;
    }

    /**
     * Funcao para atualizar um evento
     * Sumario, Descrição, Hora Inicio, Hora Fim
     * @return Obj objeto do evento atualizado
     */
    public function updateEvents($body){
        $this->id_event = $body['event_id'];
        if(!$this->calendar){
            $this->setCalendar($this->calendar_id);
        }
        if(!$this->service){
            $this->setCalendarService($this->client);
        }
        
        $event = $this->service->events->get($this->calendar_id,$this->id_event);

        if(isset($body['summary'])){
          $event->setSummary($body['summary']);
        }

        if(isset($body['description'])){
             $event->setDescription($body['description']);
        }

        //inicio e fim 
        $serviceDateTime = new Google_Service_Calendar_EventDateTime();
        if(isset($body['start_datetime'])){
            $start = $serviceDateTime;
            $start->setDateTime($body['start_datetime']);
            $start->setTimeZone('America/Sao_Paulo');
            $event->setStart($start);
        }
        if(isset($body['end_datetime'])){
            $end = $serviceDateTime;
            $end->setDateTime($body['end_datetime']);
            $end->setTimeZone('America/Sao_Paulo');
            $event->setEnd($end);
        }

        //participantes...
    
        $updateEvent = $this->service->events->update($this->calendar_id, $this->id_event, $event);
        return $updateEvent;
    }

    /**
     * Funcao para deletar um evento
     * Evento ID, Calendario ID('primary')
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
     * Funcao para listar CALENDARIOS 
     * 
     * @return Obj de todos os calendarios
     */
    public function getCalendars(){
        if(!$this->service){
            $this->setCalendarService($this->client);
        }
        $optParams = 'maxResults';
        $results = $this->service->calendarList->listCalendarList();
        return $results;
     }

    /**
     * Funcao para criar um novo CALENDARIO 
     * Sumario
     * @return Msg com o nome do CALENDARIO 
     */
    public function newCalendar($body){
        if(!$this->service){
            $this->setCalendarService($this->client);
        }
        $calendarEntry =  new Google_Service_Calendar_Calendar();
        $calendarEntry->setSummary($body['summary']);
        $createdCalendar = $this->service->calendars->insert($calendarEntry);
        return $createdCalendar->getSummary();
     }

     //UPDATE CALENDAR
  
    /**
     * Funcao para deletar um CALENDARIO
     * Calendario ID
     * @return Msg deletado
     */
     public function deleteCalendar($body){
        if(!$this->service){
            $this->setCalendarService($this->client);
        }
        try{
            $deleteCalendar = $this->service->calendars->delete($body['calendar_id']);
            return "deletado";
       }catch (Exception $e) {
           echo 'Calendario não encontrado: ',  $e->getMessage(), "\n";
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

    /**
     * Funcao para criar um novo serviço do calendar
     */
    public function setCalendarService($client){
        $this->service = new Google_Service_Calendar($this->client);
    }

    private $calendar = null;
    private $service = null;
    private $is_authenticated = false;
    private $code;
    private $token;
    private $calendar_id = "primary";
    //private $redirect_uri = "https://cherobim.innovaweb.com.br/allan/callback.php";
    private $redirect_uri = "http://localhost:8000/callback.php";
}