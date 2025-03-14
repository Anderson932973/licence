# Módulo Contrate Licenças para Perfex CRM

## Descrição
Este módulo permite o gerenciamento de licenças baseadas em domínio e data para outros módulos do Perfex CRM. Ideal para controlar o acesso e período de uso de módulos vendidos a clientes.

## Funcionalidades
- Geração automática de chaves de licença
- Controle de data de início e término
- Validação por domínio
- Log de atividades
- Gestão de status (ativa, inativa, expirada)
- Interface administrativa completa
- Suporte a múltiplos clientes e módulos

## Requisitos
- Perfex CRM 2.3.* ou superior
- PHP 7.3 ou superior
- MySQL 5.6 ou superior

## Instalação
1. Faça o download do módulo
2. Extraia os arquivos para a pasta `/modules` do seu Perfex CRM
3. Acesse o painel administrativo
4. Ative o módulo em Configurações > Módulos

## Uso
### Para Administradores
1. Acesse o menu "Contrate Licenças" no painel administrativo
2. Clique em "Nova Licença" para criar uma licença
3. Preencha os dados necessários:
   - Cliente
   - Domínio
   - Nome do Módulo
   - Data de Início
   - Data de Término
4. A chave da licença será gerada automaticamente

### Para Desenvolvedores
Para validar uma licença em seu módulo, utilize o seguinte código:

```php
$this->load->model('contrate_licencas/contrate_licencas_model');
$result = $this->contrate_licencas_model->validate_license($license_key, $domain, $module_name);

if (!$result['valid']) {
    throw new Exception($result['message']);
}
```

## Changelog

### Versão 1.0.0
- Lançamento inicial do módulo
- Sistema completo de gerenciamento de licenças
- Interface administrativa
- Sistema de logs
- Validação de licenças
- Suporte a múltiplos idiomas (Português-BR)
