<?php

class Timestamp
{
	private $historia = [];
	private $log = [];

	public function Timestamp ($h)
	{
		$this->historia = $this->convert($h);
	}
	
	public function convert($h)
	{
		$x = explode(";", $h);
		$ah = [];
		if (count($x) == 1)
			throw new Exception("História mal escrita. Separador é ';'");
		foreach($x as $v)
		{
			$a = explode("-", $v);
			if(count($a) != 2)
				throw new Exception("História mal escrita. Separador é '-'");
			$t = substr($a[0], 1, 1);
			if(!is_numeric($t))
				throw new Exception("História mal escrita. Transação é número");
			$rw = substr($a[1], 0, 1);
			if($rw != "W" && $rw != "R")
				throw new Exception("História mal escrita. Use somente 'W' e 'R'");
			$var = substr($a[1], -2, 1);
			if(!ctype_alpha($var))
				throw new Exception("História mal escrita. Use uma letra para representar a variável");
			$ah[] = [$t => [$rw => $var]];
		}
		return $ah;
	}

	public function gerarHistoriaFinal()
	{
		$w_max = [];
		$r_max = [];
		$t_aborts = [];
		foreach($this->historia as $k1 => $v1)
		{
			foreach($v1 as $k => $v)
			{
				if(!in_array($k, $t_aborts)) 
				{
					foreach($v as $key => $value)
					{
						if ($key == "W") // escrita
						{
							if((!empty($r_max[$value]) && $k < $r_max[$value]) || (!empty($w_max[$value]) && $k < $w_max[$value]))
							{
								// rejeitada
								$t_aborts[] = $k;
							}
							else if((!empty($w_max[$value]) && $w_max[$value] < $k) || empty($w_max[$value])) 
							{
								$w_max[$value] = $k;
								$this->log[] = "Novo W-TS($value): $k";
							}
						}
						else // leitura
						{
							if(!empty($w_max[$value]) && $k < $w_max[$value])
							{
								// rejeitada
								$t_aborts[] = $k;
							}
							else if ((!empty($r_max[$value]) && $r_max[$value] < $k) || empty($r_max[$value]))
							{
								$r_max[$value] = $k;
								$this->log[] = "Novo R-TS($value): $k";
							}
						}
					}
				}
			}
		}
		if(count($t_aborts) > 0)
		{
			foreach($t_aborts as $n_transacao)
			{
				$this->log[] = "Transação abortada: $n_transacao";
				$t_abortada = $this->removerTransacao($n_transacao);
				$this->historia = array_merge($this->historia, $t_abortada);
			}
		}
		return $this->historia;
	}

	private function removerTransacao($n_transacao)
	{
		$new_h = [];
		$t = [];
		foreach ($this->historia as $k1 => $v1)
		{
			foreach($v1 as $k => $v)
			{
				foreach ($v as $key => $value)
				{
					if($k != $n_transacao)
					{
						$new_h[] = [$k => [$key => $value]];
					}
					else
					{
						$t[] = [$k => [$key => $value]];
					}
				}
			}
		}
		$this->historia = $new_h;
		return $t;
	}
	
	public function getLog()
	{
		return $this->log;
	}

	public function convertBack()
	{
		$h = "";
		foreach ($this->historia as $k1 => $v1)
		{
			foreach($v1 as $k => $v)
			{
				foreach($v as $key => $value)
				{
					$h .= "T$k-$key($value);";
				}
			}
		}
		return $h;
	}
}

$h = "T4-R(w);T2-R(x);T4-W(w);T2-R(w);T1-R(x);T3-R(z);T2-W(x);T1-W(x);T3-W(z)";
//$h = "T2-R(x);T2-R(w);T1-R(x);T3-R(z);T2-W(x);T1-W(x);T3-W(z)";

try
{
	$timestamp = new Timestamp($h);
	$timestamp->gerarHistoriaFinal();
	echo "Transação original: <br/>$h<br/>";
	echo "Log:<br/>";
	echo implode($timestamp->getLog(), "<br/>"), "<br/>";
	echo "História final:<br/>";
	echo $timestamp->convertBack();
}
catch (Exception $e)
{
	echo $e->getMessage(), "<br/>";
}

?>