<?php
	class AverageTemperature extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("TemperatureVariables", "");
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

			$ident = "Temperature";
			$this->RegisterVariableFloat($ident, $ident, "~Temperature", 1);

			$temperatureVariables = $this->getRegisteredTemperatureVariables();

			if($temperatureVariables != NULL) {
				foreach($temperatureVariables as $temperatureVariable) {
					//IPS_LogMessage("Averaging", $temperatureVariable["VariableID"]);
					$this->RegisterMessage($temperatureVariable->VariableID, VM_UPDATE);
				}
			}
		}

		public function MessageSink($timestamp, $senderId, $message, $data) {
			$temperatureVariables = $this->getRegisteredTemperatureVariables();
			if($temperatureVariables == NULL) return;

			$variableIsValid = false;
			foreach($temperatureVariables as $temperatureVariable) {
				if($temperatureVariable->VariableID == $senderId) {
					$variableIsValid = true;
					break;
				}
			}

			if(!$variableIsValid) {
				$this->UnregisterMessage($senderId, VM_UPDATE);
				IPS_LogMessage("Averaging", sprintf("Unregistered from sender %d", $senderId));
				return;
			}

			$averageTemperature = $this->calculateAverageTemperature();
			$this->SetValue("Temperature", $averageTemperature);
			//IPS_LogMessage("Averaging", "Message from SenderID ".$senderId." with Message ".$message."\r\n Data: ".print_r($data, true));
		}

		private function calculateAverageTemperature() {
			$averageTemperature = 0.0;
			$totalWeight = 0;
			$maxAge = 6; //hours
			$temperatureVariables = $this->getRegisteredTemperatureVariables();
			foreach($temperatureVariables as $temperatureVariable) {
				$varInfo = IPS_GetVariable($temperatureVariable->VariableID);
				if(time() - $varInfo["VariableUpdated"] > 60*60*$maxAge) {
					IPS_LogMessage("Averaging", sprintf("Skipping %d due to age (last updated at %s, older than %d hours)", $temperatureVariable->VariableID, date("H:i:s", $varInfo["VariableUpdated"]), $maxAge));
					continue;
				}
				$temperature = GetValueFloat($temperatureVariable->VariableID);
				$averageTemperature += ($temperature * $temperatureVariable->Weight);
				$totalWeight += $temperatureVariable->Weight;
			}
			if($averageTemperature == 0) {
				return 0;
			}

			$averageTemperature /= $totalWeight;
			return $averageTemperature;
		}

		private function getRegisteredTemperatureVariables() {
			$temperatureVariablesJson = $this->ReadPropertyString("TemperatureVariables");
			$result = json_decode($temperatureVariablesJson);
			return (json_last_error() == JSON_ERROR_NONE) ? $result : NULL;
		}

	}