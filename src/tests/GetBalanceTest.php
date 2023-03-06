<?php

namespace Tests\Unit\libs\finance;

use Provider;
use Settings;
use Tests\TestCase;
use User;

// Test OK
// Run: sail artisan test --filter GetBalanceTest
class GetBalanceTest extends TestCase {

	/* Testes de success em visualizar o saldo */


    /**
     * Teste de configurações habilitadas do user para ver o saldo
     * 
     * @return void
     */
    public function testSettingsBalanceUserEnabledSuccess()
    {
        $response = $this->updateSettings('user', true);
        $this->assertTrue($response);
    }

    /**
     * Teste de configurações habilitadas do provider para ver o saldo
     * 
     * @return void
     */
    public function testSettingsBalanceProviderEnabledSuccess()
    {
        $response = $this->updateSettings('provider', true);
        $this->assertTrue($response);
    }

    /**
     * Teste de retorno na api application settings
     * 
     * @return void
     */
    public function testApiApplicationBalanceEnabled()
    {
        $settingsApi = $this->getSettings();
        $this->assertTrue($settingsApi->show_user_balance);
        $this->assertTrue($settingsApi->show_provider_balance);
    }

    /**
     * Teste para retornar o saldo do usuário com sucesso
     * 
     * @return void
     */
    public function testGetBalanceUserSuccess()
    {
        $user = User::randomForTest();

        // Parâmetros da requisição 
        $params = array(
            'user_id'   => $user->id,
            'token'     => $user->token,
        );

        // Realiza o request no endpoint
		$response = $this->call('POST', '/libs/finance/get_balance', $params);
        
        // Pega o resultado da requisição
        $apiResult = json_decode($response->getContent());
        
        // Passa os asserts necessários
		$this->assertTrue($apiResult->success);
    }

    /**
     * Teste para retornar o saldo do provider com sucesso
     * 
     * @return void
     */
    public function testGetBalanceProviderSuccess()
    {
        $provider = Provider::randomForTest();

        // Parâmetros da requisição 
        $params = array(
            'provider_id'   => $provider->id,
            'token'         => $provider->token,
        );

        // Realiza o request no endpoint
		$response = $this->call('POST', '/libs/finance/get_balance', $params);
        
        // Pega o resultado da requisição
        $apiResult = json_decode($response->getContent());
        
        // Passa os asserts necessários
		$this->assertTrue($apiResult->success);
    }


	/* Testes desabilitando as configurações de visualizar o saldo */

    /**
     * Teste para desativar o saldo do usuário com sucesso
     * 
     * @return void
     */
    public function testSettingsUserBalanceDisabled()
    {
        $response = $this->updateSettings('user', false);
        $this->assertTrue($response);
    }


    /**
     * Teste para desativar o saldo do Provider com sucesso
     * 
     * @return void
     */
    public function testSettingsProviderBalanceDisabled()
    {
        $response = $this->updateSettings('provider', false);
        $this->assertTrue($response);
    }


    /**
     * Teste de retorno na api application settings
     * 
     * @return void
     */
    public function testApiApplicationBalanceDisabled()
    {
        $settingsApi = $this->getSettings();
        $this->assertFalse($settingsApi->show_user_balance);
        $this->assertFalse($settingsApi->show_provider_balance);
    }

    /**
     * Teste para verificar se está desabilitado o saldo do usuário com sucesso
     * 
     * @return void
     */
    public function testDisabledGetBalanceUserSuccess()
    {
        $user = User::randomForTest();

        // Parâmetros da requisição 
        $params = array(
            'user_id'   => $user->id,
            'token'     => $user->token,
        );

        // Realiza o request no endpoint
		$response = $this->call('POST', '/libs/finance/get_balance', $params);
        
        // Pega o resultado da requisição
        $apiResult = json_decode($response->getContent());
        
        // Passa os asserts necessários
		$this->assertFalse($apiResult->success);
		$this->assertIsArray($apiResult->errors);
		$this->assertEquals($apiResult->error_code, 402);
    }


    /**
     * Teste para verificar se está desabilitado o saldo do provider com sucesso
     * 
     * @return void
     */
    public function testDisabledGetBalanceProviderSuccess()
    {
        $provider = Provider::randomForTest();

        // Parâmetros da requisição 
        $params = array(
            'provider_id' => $provider->id,
            'token'       => $provider->token,
        );

        // Realiza o request no endpoint
		$response = $this->call('POST', '/libs/finance/get_balance', $params);
        
        // Pega o resultado da requisição
        $apiResult = json_decode($response->getContent());
        
        // Passa os asserts necessários
		$this->assertFalse($apiResult->success);
		$this->assertIsArray($apiResult->errors);
		$this->assertEquals($apiResult->error_code, 402);
    }

	/* Erros em caso de falhas */
    
    /**
     * Teste para verificar se está com erro o saldo do usuário com sucesso
     * 
     * @return void
     */
    public function testGetBalanceUserError()
    {
        $user = User::randomForTest();

        // Parâmetros da requisição erradas
        $params = array(
            'provider_id'   => $user->id,
            'token'     => $user->token,
        );

        // Realiza o request no endpoint
		$response = $this->call('POST', '/libs/finance/get_balance', $params);
        
        // Passa os asserts necessários
		$this->assertEquals($response->getStatusCode(), 404);
        
    }

    /**
     * Teste para verificar se está com erro o saldo do provider com sucesso
     * 
     * @return void
     */
    public function testGetBalanceProviderError()
    {
        $provider = Provider::randomForTest();

        // Parâmetros da requisição erradas
        $params = array(
            'user_id'   => $provider->id,
            'token'     => $provider->token,
        );

        // Realiza o request no endpoint
		$response = $this->call('POST', '/libs/finance/get_balance', $params);
        
        // Passa os asserts necessários
		$this->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * Get api application settings
     * @param string $userType | Default: 'user'
     * @return object
     */
    private function getSettings(string $userType = 'user')
    {
		$response = $this->call('GET', "api/v3/application/settings?user_type=$userType");
        $apiResult = json_decode($response->getContent());
		return $apiResult;   
    }

    /**
     * Update settings 
     * @param string $userType
     * @param bool $value
     * @return bool
     */
    private function updateSettings(string $userType, bool $value): bool
    {
        $settingBalance = Settings::findObjectByKey('show_' . $userType . '_balance');
        $settingBalance->value = $value;
        return $settingBalance->save();
    }
}
