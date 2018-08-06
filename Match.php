<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Match
 *
 * @author namustaf
 */
class Match {
    
    /*
     * BOWLER TYPES
     * 1 - FAST
     * 2 - FAST MEDIUM
     * 3 - OFF SPIN
     * 4 - LEG SPIN
     * 5 - CHINAMAN
    */
    
    /*
     * ROLE
     * 1 - PURE BATSMAN
     * 2 - PURE BOWLER
     * 3 - WICKETKEEPER
     * 4 - BATTING ALLROUNDER
     * 5 - BOWLING ALLROUNDER
    */
    
    /*
     * BATSMAN MENTALITY
     * 1 - AGGRESSIVE
     * 2 - MODERATE
     * 3 - DEFENSIVE
    */
    
    /*
     * MATCH MODES
     * 1 - ODI
     * 2 - T20
     * 3 - CUSTOM
    */
    
    const PURE_BATSMAN = 1;
    const PURE_BOWLER = 2;
    const WICKETKEEPER = 3;
    const BATTING_ALLROUNDER = 4;
    const BOWLING_ALLROUNDER = 5;
    
    const AGGRESIVE_MENTALITY = 1;
    const MODERATE_MENTALITY = 2;
    const DEFENSIVE_MENTALITY = 3;
    
    const ODI = 1;
    const T20 = 2;
    const CUSTOM_MATCH_MODE = 3;
    
    public $matchId = null;
    public $matchMode = null;
    public $matchOvers = 0;
    public $pitchType = null;
    
    public $battingTeamId = null;
    public $strikerId = null;
    public $nonStrikerId = null;
    public $batsmen = array();
    public $batsmanRP = 0;
    public $batsmanRole = null;
    public $batsmanMentality = null;
    
    
    public $bowlingTeamId = null;
    public $bowlerId = null;
    public $bowlerPosition = null;
    public $bowlers = array();
    public $bowlerRP = 0;
    public $bowlerRole = null;    
    public $deliveryOutcome = null;
    
    
    public $inningsScore = null;
    public $inningsWickets = null;    
    public $inningsBallsBowled = null;
    public $inningsWides = null;
    public $inningsNoBalls = null;
    public $inningsByes = null;
    public $inningsLegByes = null;
    public $inningsStage = null;
    
    public $maxBallsPerBowler = 0;
    public $playerAdvantage = null;
    
    public function __construct($id)
    {
        $this->matchId = $id;
        $this->matchMode = 2;
        $this->matchOvers = 20;
        $this->maxBallsPerBowler = ($this->matchOvers*6)/5;
        
        // get the team ids from database, for now it's hardcoded
        $this->battingTeamId = 1;
        $this->bowlingTeamId = 2;
        
        $this->SetOpeners();
        $this->SetOpeningBowler();
        
        $this->PrepareNewInnings();
    }
    
    private function assignDeliveries($pid)
    {
        $bowler = $this->bowlers[$pid];
        $bank = array();
        $deliveries = array();
        
        $gb = $bowler['GB'];
        $bb = $bowler['BB'];
        $sb = $bowler['SB'];
        $wb = $bowler['WB'];
        $eb = $bowler['EB'];
        
        for($i = 0; $i < $gb; $i++)
        {
            $bank[] = 'G';
        }
        
        for($i = 0; $i < $bb; $i++)
        {
            $bank[] = 'B';
        }
        
        for($i = 0; $i < $sb; $i++)
        {
            $bank[] = 'S';
        }
        
        for($i = 0; $i < $wb; $i++)
        {
            $bank[] = 'W';
        }
        
        for($i = 0; $i < $eb; $i++)
        {
            $bank[] = 'E';
        }
        
        shuffle($bank);
        
        $i = 1;
        while($element = array_pop($bank))
        {
            $deliveries[$i] = $element;
            shuffle($bank);
            $i++;
        }
        
        $this->bowlers[$pid]['deliveries'] = $deliveries;
    }
    
    private function BoundaryResult($percent)
    {
        $dot_balls = round((100 - $percent)/10); // eg: 100 - 70 => 30/10 => 3
        $run_balls = 10 - $dot_balls;
        $bank = array(4,6);
        
        $result = array();
        if($run_balls > 0)
        {
            while($run_balls > 0)
            {            
                $result[] = $bank[array_rand($bank)];             
                $run_balls--;
            }
        }
        
        if($dot_balls > 0)
        {
            while($dot_balls > 0)
            {
                $result[] = 0;
                $dot_balls--;
            }
        }
        
        if(count($result) > 0)
        {
            $result[] = 3;
            $result[] = 3;
            shuffle($result);
            return $result[array_rand($result)];
        }
        else
        {
            $this->Error("BoundaryResult returned an empty array. Percent is ".$percent);
        }
    }
    
    private function CalculatePercentages($pid)
    {
        $rp = $this->bowlers[$pid]['rp'];
        $role = $this->bowlers[$pid]['role'];
        
        if($rp >= 95)
        {
            $good = ($role == self::PURE_BOWLER ? 30 : 25);
            $bad = 10;
            $wicket = 25;
            $extras = ($role == self::PURE_BOWLER ? mt_rand(0, 1) : mt_rand(0, 2));
        }
        else if($rp >=85 && $rp <= 94)
        {
            $good = ($role == self::PURE_BOWLER ? 25 : 20);
            $bad = 15;
            $wicket = 20;
            $extras = ($role == self::PURE_BOWLER ? mt_rand(0, 1) : mt_rand(0, 5));
        }
        else if($rp >= 70 && $rp <= 84)
        {
            $good = ($role == self::PURE_BOWLER ? 20 : 15);
            $bad = 20;
            $wicket = 15;
            $extras = mt_rand(0, 5);
        }
        else if($rp >= 50 && $rp <= 69)
        {
            $good = 10;
            $bad = 30;
            $wicket = 10;
            $extras = mt_rand(0, 10);
        }
        else
        {
            $good = 5;
            $bad = 40;
            $wicket = 5;
            $extras = mt_rand(0,10);
        }
                
        // convert that to actual numbers now taking the % into account
        $good_balls = ceil($good*$this->maxBallsPerBowler/100);
        $bad_balls = ceil($bad*$this->maxBallsPerBowler/100);
        $wicket_balls = ceil($wicket*$this->maxBallsPerBowler/100);
        $extra_balls = ceil($extras*$this->maxBallsPerBowler/100);
        $stock_balls = $this->maxBallsPerBowler - ($good_balls + $bad_balls + $wicket_balls + $extra_balls);
        
        $this->bowlers[$pid]['GB'] = $good_balls;
        $this->bowlers[$pid]['BB'] = $bad_balls;
        $this->bowlers[$pid]['WB'] = $wicket_balls;
        $this->bowlers[$pid]['EB'] = $extra_balls;
        $this->bowlers[$pid]['SB'] = $stock_balls;
    }
    
    private function Error($msg)
    {
        die($msg);
    }
    
    private function GetCurrentBowlerAttributes()
    {
        if(isset($this->bowlers[$this->bowlerId]))
        {
            $bowl = $this->bowlers[$this->bowlerId];
            $this->bowlerRP = $bowl['rp'];
            $this->bowlerRole = $bowl['role'];
        }
        else
        {
            $this->Error("Bowler attributes could not be fetched because bowler not found");
        }
    }
    
    private function GetStrikerAttributes()
    {
        if(isset($this->batsmen[$this->strikerId]))
        {
            $bat = $this->batsmen[$this->strikerId];
            $this->batsmanRP = $bat['rp'];
            $this->batsmanRole = $bat['role'];
            $this->batsmanMentality = $bat['ment'];
        }
        else
        {
            $this->Error("Batsman attributes could not be fetched because batsman not found");
        }
    }
    
    private function GetDeliveryOutcome()
    {
        $bowler = $this->bowlers[$this->bowlerId];
        $balls_bowled = (int) $bowler['balls_delivered'];
        $type_of_ball = $bowler['deliveries'][($balls_bowled+1)];
        
        return $this->SendToBatsman($type_of_ball);
    }
    
    private function GenerateDeliveries()
    {
        if(count($this->bowlers) > 4)
        {
            foreach($this->bowlers as $player_id => $attrs)
            {
                $this->CalculatePercentages($player_id);
                $this->assignDeliveries($player_id);
            }
        }
        else
        {
            $this->Error("Number of bowlers found to be less than 5. It should be at least 5 or more.");
        }
    }
    
    private function GetBowlingOptions()
    {
        $data = array();
        $data[1] = array('name'=>'James Anderson', 'rp'=>82, 'role'=>2, 'type'=>2, 'runs_conceded'=>0, 'balls_delivered'=>0, 'wides'=>0, 'no_balls'=>0, 'wickets'=>0, 'maidens'=>0);
        $data[2] = array('name'=>'Stuart Broad', 'rp'=>88, 'role'=>2, 'type'=>2, 'runs_conceded'=>0, 'balls_delivered'=>0, 'wides'=>0, 'no_balls'=>0, 'wickets'=>0, 'maidens'=>0);
        $data[3] = array('name'=>'Adil Rashid', 'rp'=>75, 'role'=>2, 'type'=>4, 'runs_conceded'=>0, 'balls_delivered'=>0, 'wides'=>0, 'no_balls'=>0, 'wickets'=>0, 'maidens'=>0);
        $data[4] = array('name'=>'Moin Ali', 'rp'=>85, 'role'=>4, 'type'=>3, 'runs_conceded'=>0, 'balls_delivered'=>0, 'wides'=>0, 'no_balls'=>0, 'wickets'=>0, 'maidens'=>0);
        $data[5] = array('name'=>'David Wiley', 'rp'=>67, 'role'=>5, 'type'=>2, 'runs_conceded'=>0, 'balls_delivered'=>0, 'wides'=>0, 'no_balls'=>0, 'wickets'=>0, 'maidens'=>0);
        $data[6] = array('name'=>'Joe Root', 'rp'=>55, 'role'=>1, 'type'=>3, 'runs_conceded'=>0, 'balls_delivered'=>0, 'wides'=>0, 'no_balls'=>0, 'wickets'=>0, 'maidens'=>0);
        
        return $data;
    }
    
    public function PrepareNewInnings()
    {
        $this->inningsScore = 0;
        $this->inningsWickets = 0;
        $this->inningsBallsBowled = 0;
        $this->inningsWides = 0;
        $this->inningsNoBalls = 0;
        $this->inningsByes = 0;
        $this->inningsLegByes = 0;
    }
    
    public function StartInnings()
    {
        $this->bowlers = $this->GetBowlingOptions();
        $this->GenerateDeliveries();
        
        while($this->inningsBallsBowled !== $this->matchOvers*6)
        {
            $this->GetCurrentBowlerAttributes();
            $this->GetStrikerAttributes();
            $this->SetPlayerAdvantage();
            $this->SetInningsStage();            
            
            if($this->inningsWickets == 10)
            {
                break;
            }
            else
            {
                $this->deliveryOutcome = $this->GetDeliveryOutcome();
            }
            $this->inningsBallsBowled++;
        }
    }
    
    private function SetPlayerAdvantage()
    {
        if($this->bowlerRP > $this->batsmanRP)
        {
            $this->playerAdvantage = 'BOWLER';
        }
        else if($this->bowlerRP < $this->batsmanRP)
        {
            $this->playerAdvantage = 'BATSMAN';
        }
        else if($this->batsmanRP === $this->bowlerRP)
        {
            $this->playerAdvantage = 'NEUTRAL';
        }
        else
        {
            $this->Error('Player advantage could not be determined. The RPs are as follows: '.$this->batsmanRP.' == '.$this->bowlerRP);
        }
    }
    
    private function SetInningsStage()
    {
        if($this->matchMode === self::ODI)
        {
            if($this->inningsBallsBowled <= 60)
            {
                $this->inningsStage = "PP";
            }
            else if($this->inningsBallsBowled > 60 && $this->inningsBallsBowled <= 240)
            {
                $this->inningsStage = "MO";
            }
            else if($this->inningsBallsBowled > 240)
            {
                $this->inningsStage = "DO";
            }
            else
            {
                $this->Error("Innings stage (ODI) could not be determined. Balls delivered = ".$this->inningsBallsBowled);
            }
        }
        else if($this->matchMode === self::T20)
        {
            if($this->inningsBallsBowled <= 36)
            {
                $this->inningsStage = "PP";
            }
            else if($this->inningsBallsBowled > 36 && $this->inningsBallsBowled <= 96)
            {
                $this->inningsStage = "MO";
            }
            else if($this->inningsBallsBowled > 96)
            {
                $this->inningsStage = "DO";
            }
            else
            {
                $this->Error("Innings stage (T20) could not be determined. Balls delivered = ".$this->inningsBallsBowled);
            }
        }
        else if($this->matchMode === self::CUSTOM_MATCH_MODE)
        {
            $total_balls_to_be_delivered = $this->matchOvers*6;
            $pp_cutoff = round($total_balls_to_be_delivered/5); // 5%
            $mo_cutoff = round($total_balls_to_be_delivered*80/100); // 80%
            
            if($this->inningsBallsBowled <= $pp_cutoff)
            {
                $this->inningsStage = "PP";
            }
            else if($this->inningsBallsBowled > $pp_cutoff && $this->inningsBallsBowled <= $mo_cutoff)
            {
                $this->inningsStage = "MO";
            }
            else if($this->inningsBallsBowled > $mo_cutoff)
            {
                $this->inningsStage = "DO";
            }
            else
            {
                $this->Error("Innings stage (Custom Mode) could not be determined. Balls delivered = ".$this->inningsBallsBowled);
            }
        }
    }
    
    private function SendToBatsman($type)
    {       
        $increment_balls_faced = true;
        
        if($this->inningsStage === 'PP')
        {
            if($type === 'S') // stock ball
            {

            }
            else if($type === 'G') // good ball
            {
                if($this->pitchType)
                {
                    // future upgrade
                    $this->Error('Pitch type to be handled in future upgrade');
                }
                else
                {
                    if($this->playerAdvantage == 'BATSMAN')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->RunResult(70);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->RunResult(50);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->RunResult(30);
                        }
                    }
                    else if($this->playerAdvantage == 'BOWLER')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(20);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(0);
                        }
                    }
                    else
                    {
                        return $this->RunResult(50);
                    }
                }
            }
            else if($type === 'B') // bad ball
            {
                if($this->playerAdvantage == 'BATSMAN')
                {
                    if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(50);
                    }
                    else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                    {
                        return $this->BoundaryResult(30);
                    }
                    else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(10);
                    }
                }
                else if($this->playerAdvantage == 'BOWLER')
                {
                    if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                    {
                        return $this->RunResultesult(70);
                    }
                    else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                    {
                        return $this->RunResultesult(50);
                    }
                    else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                    {
                        return $this->RunResultesult(30);
                    }
                }
                else
                {
                    return $this->BoundaryResult(40);
                }
            }
            else if($type === 'W')// wicket taking ball
            {
                if($this->pitchType)
                {
                    // future upgrade
                    $this->Error('Pitch type to be handled in future upgrade');
                }
                else
                {
                    if($this->playerAdvantage == 'BATSMAN')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(20);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(15);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }
                    }
                    else if($this->playerAdvantage == 'BOWLER')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(35);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(25);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(20);
                        }
                    }
                    else
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(25);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(15);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }
                    }
                }
            }
            else if($type === 'E') // extra ball
            {
                $increment_balls_faced = false;
            }        
            else
            {
                $this->Error("Type of delivery (G,B,W,E,S) could not be determined in PP stage.");
            }
        }
        else if($this->inningsStage === 'MO')
        {
            if($type === 'S') // stock ball
            {

            }
            else if($type === 'G') // good ball
            {
                if($this->pitchType)
                {
                    // future upgrade
                    $this->Error('Pitch type to be handled in future upgrade');
                }
                else
                {
                    if($this->playerAdvantage == 'BATSMAN')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->RunResult(50);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY || $this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->RunResult(30);
                        }                        
                    }
                    else if($this->playerAdvantage == 'BOWLER')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(20);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY || $this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }                        
                    }
                    else
                    {
                        return $this->RunResult(40);
                    }
                }                
            }
            else if($type === 'B') // bad ball
            {
                if($this->playerAdvantage == 'BATSMAN')
                {
                    if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(40);
                    }
                    else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                    {
                        return $this->BoundaryResult(30);
                    }
                    else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(10);
                    }
                }
                else if($this->playerAdvantage == 'BOWLER')
                {
                    if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                    {
                        return $this->RunResultesult(50);
                    }
                    else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                    {
                        return $this->RunResultesult(30);
                    }
                    else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                    {
                        return $this->RunResultesult(20);
                    }
                }
                else
                {
                    return $this->RunResult(90);
                }
            }
            else if($type === 'W')// wicket taking ball
            {
                if($this->pitchType)
                {
                    // future upgrade
                    $this->Error('Pitch type to be handled in future upgrade');
                }
                else
                {
                    if($this->playerAdvantage == 'BATSMAN')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(30);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY || $this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(25);
                        }
                    }
                    else if($this->playerAdvantage == 'BOWLER')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(40);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY || $this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(30);
                        }                        
                    }
                    else
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(25);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(15);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }
                    }
                }
                
            }
            else if($type === 'E') // extra ball
            {
                $increment_balls_faced = false;
            }        
            else
            {
                $this->Error("Type of delivery (G,B,W,E,S) could not be determined in MO stage.");
            }
        }
        else if($this->inningsStage === 'DO')
        {
            if($type === 'S') // stock ball
            {

            }
            else if($type === 'G') // good ball
            {
                if($this->pitchType)
                {
                    // future upgrade
                    $this->Error('Pitch type to be handled in future upgrade');
                }
                else
                {    
                    if($this->playerAdvantage == 'BATSMAN')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->BoundaryResult(40);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->BoundaryResult(30);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->BoundaryResult(20);
                        }
                    }
                    else if($this->playerAdvantage == 'BOWLER')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(30);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(20);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }
                    }
                    else
                    {
                        return $this->BoundaryResult(50);
                    }
                }
            }
            else if($type === 'B') // bad ball
            {
                if($this->playerAdvantage == 'BATSMAN')
                {
                    if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(70);
                    }
                    else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                    {
                        return $this->BoundaryResult(60);
                    }
                    else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(40);
                    }
                }
                else if($this->playerAdvantage == 'BOWLER')
                {
                    if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(50);
                    }
                    else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                    {
                        return $this->BoundaryResult(40);
                    }
                    else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                    {
                        return $this->BoundaryResult(30);
                    }
                }
                else
                {
                    return $this->BoundaryResult(60);
                }
            }
            else if($type === 'W')// wicket taking ball
            {
                if($this->pitchType)
                {
                    // future upgrade
                    $this->Error('Pitch type to be handled in future upgrade');
                }
                else
                {
                    if($this->playerAdvantage == 'BATSMAN')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(40);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY || $this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(35);
                        }
                    }
                    else if($this->playerAdvantage == 'BOWLER')
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(50);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY || $this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(40);
                        }                        
                    }
                    else
                    {
                        if($this->batsmanMentality === self::AGGRESIVE_MENTALITY)
                        {
                            return $this->WicketResult(25);
                        }
                        else if($this->batsmanMentality === self::MODERATE_MENTALITY)
                        {
                            return $this->WicketResult(15);
                        }
                        else if($this->batsmanMentality === self::DEFENSIVE_MENTALITY)
                        {
                            return $this->WicketResult(10);
                        }
                    }
                }
            }
            else if($type === 'E') // extra ball
            {
                $increment_balls_faced = false;
            }        
            else
            {
                $this->Error("Type of delivery (G,B,W,E,S) could not be determined in DO stage.");
            }
        }
        else
        {
            $this->Error("Stage of innings could not be determined. It's current value is ".$this->inningsStage);
        }
    }
    
    private function SetOpeners()
    {
        // get the openers from datbase based on $battingTeamId, for now it's hardcoded
        $this->strikerId = 1;
        $this->nonStrikerId = 2;
    }
    
    private function SetOpeningBowler()
    {
        // get the opening bowler from databased based on $bowlingTeamId, for now it's hardcoded
        $this->bowlerId = 1;
        $this->bowlerPosition = 1;
    }
    
    private function RunResult($percent)
    {
        $dot_balls = round((100 - $percent)/10); // eg: 100 - 70 => 30/10 => 3
        $run_balls = 10 - $dot_balls;
        $bank = array(1,2);
        
        $result = array();
        if($run_balls > 0)
        {
            while($run_balls > 0)
            {            
                $result[] = $bank[array_rand($bank)];             
                $run_balls--;
            }
        }
        
        if($dot_balls > 0)
        {
            while($dot_balls > 0)
            {
                $result[] = 0;
                $dot_balls--;
            }
        }
        
        if(count($result) > 0)
        {
            shuffle($result);
            return $result[array_rand($result)];
        }
        else
        {
            $this->Error("RunResult returned an empty array. Percent is ".$percent);
        }
    }
    
    private function WicketResult($percent)
    {
        $dot_balls = round((100 - $percent)/10); // eg: 100 - 70 => 30/10 => 3
        $wicket_balls = 10 - $dot_balls;
        $bank = array(0,1);
        
        $result = array();
        if($wicket_balls > 0)
        {
            while($wicket_balls > 0)
            {            
                $result[] = "WICKET";
                $wicket_balls--;
            }
        }
        
        if($dot_balls > 0)
        {
            while($dot_balls > 0)
            {
                $result[] = $bank[array_rand($bank)];
                $dot_balls--;
            }
        }
        
        if(count($result) > 0)
        {
            shuffle($result);
            return $result[array_rand($result)];
        }
        else
        {
            $this->Error("WicketResult returned an empty array. Percent is ".$percent);
        }
    }
}
