<?php declare(strict_types = 1);

namespace iFixit\Akeneo\iFixitBundle;

class iFixitConfig {
   private const CONFIG_FILE = "/etc/dozuki/akeneo.json";
   private $config = null;

   public function get(string $key) {
      if ($this->config === null) {
         $this->config = $this->loadConfig();
      }
      return $this->config[$key];
   }

   private function loadConfig(): array {
      $json = file_get_contents(self::CONFIG_FILE);
      $config = json_decode($json, true); // get arrays
      if (json_last_error()) {
         throw new \Exception(json_last_error_msg());
      }
      return $config;
   }
}
