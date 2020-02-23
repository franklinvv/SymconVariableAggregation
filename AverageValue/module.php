<?php
	class AverageValue extends IPSModule {

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

			$ident = "Average";
			$this->RegisterVariableFloat($ident, $ident);

			$variables = $this->getRegisteredVariables();

			if($variables != NULL) {
				foreach($variables as $variable) {
					//IPS_LogMessage("Averaging", $variable["VariableID"]);
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
				IPS_LogMessage("AverageValue", sprintf("Unregistered from sender %d", $senderId));
				return;
			}

			$averageValue = $this->calculateAverageValue();
			$this->SetValue("Average", $averageValue);
			//IPS_LogMessage("Averaging", "Message from SenderID ".$senderId." with Message ".$message."\r\n Data: ".print_r($data, true));
		}

		private function calculateAverageValue() {
			$averageValue = 0.0;
			$totalWeight = 0;
			$maxAge = 6; //hours
			$variables = $this->getRegisteredVariables();
			foreach($variables as $variable) {
				$varInfo = IPS_GetVariable($variable->VariableID);
				if(time() - $varInfo["VariableUpdated"] > 60*60*$maxAge) {
					IPS_LogMessage("AverageValue", sprintf("Skipping %d due to age (last updated at %s, older than %d hours)", $variable->VariableID, date("H:i:s", $varInfo["VariableUpdated"]), $maxAge));
					continue;
				}
				$value = GetValueFloat($variable->VariableID);
				$averageValue += ($value * $variable->Weight);
				$totalWeight += $variable->Weight;
			}
			if($averageValue == 0) {
				return 0;
			}

			$averageValue /= $totalWeight;
			return $averageValue;
		}

		private function getRegisteredVariables() {
			$variablesJson = $this->ReadPropertyString("Variables");
			$result = json_decode($variablesJson);
			return (json_last_error() == JSON_ERROR_NONE) ? $result : NULL;
		}

	}