<?php
	class MaxValue extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("Variables", "");
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

			$ident = "Max";
			$this->RegisterVariableInteger($ident, $ident);

			$variables = $this->getRegisteredVariables();

			if($variables != NULL) {
				foreach($variables as $variable) {
					IPS_LogMessage("MaxValue", sprintf("Registering to %d", $variable->VariableID));
					$this->RegisterMessage($variable->VariableID, VM_UPDATE);
				}
			}
		}

		public function MessageSink($timestamp, $senderId, $message, $data) {
			$variables = $this->getRegisteredVariables();
			if($variables == NULL) return;

			$variableIsValid = false;
			foreach($variables as $variable) {
				if($variable->VariableID == $senderId) {
					$variableIsValid = true;
					break;
				}
			}

			if(!$variableIsValid) {
				$this->UnregisterMessage($senderId, VM_UPDATE);
				IPS_LogMessage("MaxValue", sprintf("Unregistered from sender %d", $senderId));
				return;
			}

			$maxValue = $this->calculateMaxValue();
			$this->SetValue("Max", $maxValue);
			IPS_LogMessage("MaxValue", "Message from SenderID ".$senderId." with Message ".$message."\r\n Data: ".print_r($data, true));
		}

		private function calculateMaxValue() {
			$maxValue = 0;

			$variables = $this->getRegisteredVariables();
			foreach($variables as $variable) {
				$value = GetValueInteger($variable->VariableID);
				if($value > $maxValue) {
					$maxValue = $value;
				}
			}

			return $maxValue;
		}

		// private function calculateAverageTemperature() {
		// 	$averageTemperature = 0.0;
		// 	$totalWeight = 0;
		// 	$maxAge = 6; //hours
		// 	$temperatureVariables = $this->getRegisteredTemperatureVariables();
		// 	foreach($temperatureVariables as $temperatureVariable) {
		// 		$varInfo = IPS_GetVariable($temperatureVariable->VariableID);
		// 		if(time() - $varInfo["VariableUpdated"] > 60*60*$maxAge) {
		// 			IPS_LogMessage("Averaging", sprintf("Skipping %d due to age (last updated at %s, older than %d hours)", $temperatureVariable->VariableID, date("H:i:s", $varInfo["VariableUpdated"]), $maxAge));
		// 			continue;
		// 		}
		// 		$temperature = GetValueFloat($temperatureVariable->VariableID);
		// 		$averageTemperature += ($temperature * $temperatureVariable->Weight);
		// 		$totalWeight += $temperatureVariable->Weight;
		// 	}
		// 	if($averageTemperature == 0) {
		// 		return 0;
		// 	}

		// 	$averageTemperature /= $totalWeight;
		// 	return $averageTemperature;
		// }

		private function getRegisteredVariables() {
			$variablesJson = $this->ReadPropertyString("Variables");
			$result = json_decode($variablesJson);
			return (json_last_error() == JSON_ERROR_NONE) ? $result : NULL;
		}

	}