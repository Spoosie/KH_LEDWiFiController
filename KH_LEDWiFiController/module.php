<?
class KH_LEDWiFiController extends IPSModule
{
    var $moduleName = "LEDWiFiController";

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // --------------------------------------------------------
        // Config Variablen
        // --------------------------------------------------------
        $this->RegisterPropertyString("ControllerIP", "127.0.0.1");
        $this->RegisterPropertyBoolean("Debug", false);
		$this->RegisterPropertyBoolean("WhiteChannel",true);

        // --------------------------------------------------------
        // Variablen Profile einrichten
        // --------------------------------------------------------

        if (!IPS_VariableProfileExists("LEDWiFi_Mode"))
        {
            IPS_CreateVariableProfile("LEDWiFi_Mode", 1);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 37, "7-stufiger Farbdurchlauf", "", 0x000000);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 38, "Rot pulsierend", "", 0xff0000);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 39, "Grün pulsierend", "", 0x00ff00);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 40, "Blau pulsierend", "", 0x0000ff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 41, "Gelb pulsierend", "", 0xffff00);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 42, "Türkis pulsierend", "", 0x00ffff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 43, "Violett pulsierend", "", 0xff00ff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 44, "Rot Grün pulsierend", "", 0xf0f000);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 45, "Rot Blau pulsierend", "", 0xf000f0);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 46, "Grün Blau pulsierend", "", 0x00f0f0);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 10, "7-stufig blitzend", "", 0xa0a0a0);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 11, "Rot blitzend", "", 0xff0000);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 12, "Grün blitzend", "", 0x00ff00);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 13, "Blau blitzend", "", 0x0000ff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 14, "Gelb blitzend", "", 0xffff00);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 15, "Türkis blitzend", "", 0x00ffff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 16, "Violett blitzend", "", 0xff00ff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 17, "Weiss blitzend", "", 0xffffff);
			IPS_SetVariableProfileAssociation("LEDWiFi_Mode", 18, "7-stufiger Farbwechsel", "", 0xa0a0a0);			
        }

        // --------------------------------------------------------
		// Variablen erzeugen
        // --------------------------------------------------------
		$varID = $this->RegisterVariableBoolean("Power", "Aktiv");
		IPS_SetVariableCustomProfile($varID,"~Switch");
		$this->EnableAction("Power"); 
		
		$varID = $this->RegisterVariableInteger("Speed", "Geschwindigkeit");
		IPS_SetVariableCustomProfile($varID,"~Intensity.100");
		$this->EnableAction("Speed"); 
		
		$varID = $this->RegisterVariableInteger("Mode", "Modus");
		IPS_SetVariableCustomProfile($varID,"LEDWiFi_Mode");
		$this->EnableAction("Mode"); 
		
		$varID = $this->RegisterVariableInteger("Color", "Farbe");
		IPS_SetVariableCustomProfile($varID,"~HexColor");
		$this->EnableAction("Color"); 
		
		$varID = $this->RegisterVariableInteger("Brightness", "Helligkeit");
		IPS_SetVariableCustomProfile($varID,"~Intensity.100");
		$this->EnableAction("Brightness"); 
		

		
    }


    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        // --------------------------------------------------------
        // IP auf String checken
        // --------------------------------------------------------
		if (filter_var($this->ReadPropertyString("ControllerIP"), FILTER_VALIDATE_IP) !== false)
			$this->SetStatus(102);
		else
			$this->SetStatus(201);
		
        // --------------------------------------------------------
		// Variablen erzeugen
        // --------------------------------------------------------
		if ($this->ReadPropertyBoolean("WhiteChannel"))
		{
			$varID = $this->RegisterVariableInteger("WarmWhite", "Warmweiß");
			IPS_SetVariableCustomProfile($varID,"~Intensity.100");
			$this->EnableAction("WarmWhite"); 
		}
		else
			$this->UnregisterVariable("WarmWhite");

    }

	
	public function RequestAction($Ident, $Value)
	{
	 
		if ($this->ReadPropertyBoolean("Debug"))
			IPS_LogMessage($this->moduleName,"RequestAction: ($Ident,$Value)");
	 
	 
		switch($Ident) {
			case "Power":
				$switchOn = array(0x71,0x23,0x0f);
				$switchOff = array(0x71,0x24,0x0f);

				if ($Value)
				{
					IPS_LogMessage($this->moduleName,"Schalte AN");
					$this->sendData($switchOn);
				}
				else
				{
					IPS_LogMessage($this->moduleName,"Schalte AUS");
					$this->sendData($switchOff);
				}
				
				SetValue($this->GetIDForIdent($Ident), $Value);
				break;
			case "Speed":
			case "Mode":
				IPS_LogMessage($this->moduleName,"Schalte Modus/Geschwindigkeit");
				SetValue($this->GetIDForIdent($Ident), $Value);
				$this->sendProgrammMix();
				break;
			case "Color":
			case "Brightness":
			case "WarmWhite":
				IPS_LogMessage($this->moduleName,"Schalte Farbe/Helligkeit/Warmweiß");
				SetValue($this->GetIDForIdent($Ident), $Value);
				$this->sendColorMix();
				break;
			default:
				throw new Exception("Invalid Ident");
		}
	 
	}

	private function sendProgrammMix()
	{
		$switchFunction = array(0x61,0x00,0x00,0x0f);

		$actualMode = GetValue($this->GetIDForIdent("Mode"));
		$actualSpeed = GetValue($this->GetIDForIdent("Speed"));

		$switchFunction[1] = $actualMode; // Mode
		$switchFunction[2] = $actualSpeed; // Speed
		
		$this->sendData($switchFunction);
	}
	
	private function sendColorMix()
	{
		$switchColor = array(0x31,0x00,0x00,0x00,0x00,0x00,0x0f);

		$actualWarmWhite = GetValue($this->GetIDForIdent("WarmWhite"));
		$actualBrightness = GetValue($this->GetIDForIdent("Brightness")) / 100;
		$actualColor = GetValue($this->GetIDForIdent("Color"));
		
		$actualRed = (($actualColor >> 16) & 0xFF);
		$actualGreen = (($actualColor >> 8) & 0xFF);
		$actualBlue = ($actualColor & 0xFF);
		
		// Mit der Helligkeit verknüpfen
		$actualRed *= $actualBrightness;
		$actualGreen *= $actualBrightness;
		$actualBlue *= $actualBrightness;
		
		$switchColor[1] = floor($actualRed);
		$switchColor[2] = floor($actualGreen);
		$switchColor[3] = floor($actualBlue);
		$switchColor[4] = $actualWarmWhite;
		$this->sendData($switchColor);
	}
	
	private function sendData($values)
	{
		$path = "tcp://".$this->ReadPropertyString("ControllerIP");
		$sockID = @fsockopen($path, 5577);
		
		if (!$sockID)
		{
			IPS_LogMessage($this->moduleName,"Keine Verbindung zu '".$path."' möglich.");
			return;
		}

		foreach($values as $value)
		{
			fwrite($sockID,chr($value));
			$datas[] = $value;
		}
		
		$sig = $this->getSignature($values);
		
		fwrite($sockID,chr($sig));
		$datas[] = $sig;
		
		if ($this->ReadPropertyBoolean("Debug"))
			IPS_LogMessage($this->moduleName,"Sende Daten=".join(",",$datas));
		
		fclose($sockID);
	}
	
	private function getSignature($values)
	{
		$signature = 97+array_sum($values)+15;
		$signature = dechex($signature);
		$signature = substr($signature, -2);
		$signature = hexdec($signature);
		
		return $signature;
	}

}
?>