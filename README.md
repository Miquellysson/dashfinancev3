
# Sistema de Gestão Financeira

Sistema completo de gestão financeira desenvolvido em PHP com arquitetura MVC, utilizando o tema SB Admin 2 para interface moderna e responsiva.

## 🚀 Características

- **Arquitetura MVC** completa e organizada
- **Interface moderna** com SB Admin 2 + Bootstrap 4
- **Dashboard interativo** com gráficos (ApexCharts)
- **Módulo de projetos avançado** com financeiro, metas e atividades
- **Gestão de usuários** com papéis, upload de avatar e auditoria
- **Biblioteca de templates** com busca e filtros
- **CRUDs completos** para Clientes, Projetos e Pagamentos
- **Sistema de autenticação** com níveis de acesso (Admin/Operador)
- **Exportação** de dados em CSV
- **Responsivo** para desktop e mobile
- **Validações** client-side e server-side

## 📋 Funcionalidades

### Dashboard
- Cards com KPIs (Clientes, Projetos, Pagamentos, Receita mensal)
- Gráfico de barras com receita por mês
- Gráfico de pizza com status dos projetos
- Tabela com últimos pagamentos

### Gestão de Clientes
- Listar, criar, editar e excluir clientes
- Campos: Nome, Email, Telefone, Endereço
- Paginação automática

### Gestão de Projetos
- Listar, criar, editar e excluir projetos
- Vinculação com clientes
- Status: Ativo, Pausado, Concluído, Cancelado
- Controle de orçamento e datas

### Gestão de Pagamentos
- Listar, criar, editar e excluir pagamentos
- Vinculação com projetos
- Status: Pendente, Pago, Cancelado
- Exportação para CSV

### Sistema de Usuários
- Login/Logout seguro
- Dois níveis: Admin e Operador
- Admins podem excluir registros
- Operadores têm acesso limitado

## 🛠️ Instalação

1. **Faça upload dos arquivos** para `public_html/app/` no seu servidor

2. **Configure o banco de dados** editando `config/database.php`:
   ```php
   $host = "localhost";
   $db   = "seu_banco";
   $user = "seu_usuario";
   $pass = "sua_senha";
   ```

3. **Importe o schema** executando `install/schema.sql` no phpMyAdmin

4. **Acesse o sistema** em `https://seudominio.com/auth/login`

## 🔐 Credenciais Padrão

- **Admin:** admin@arkaleads.com / admin123
- **Operador:** operador@arkaleads.com / admin123

## 📁 Estrutura do Projeto

```
gestao_financeira_completo/
├── index.php                 # Roteador principal
├── .htaccess                 # Configuração Apache
├── app/
│   ├── Controllers/          # Controladores MVC
│   ├── Models/              # Modelos de dados
│   ├── Views/               # Templates das páginas
│   └── Helpers/             # Classes auxiliares
├── assets/
│   ├── css/                 # Estilos customizados
│   └── js/                  # JavaScript customizado
├── config/
│   ├── database.php         # Configuração do banco
│   └── .env.php            # Variáveis de ambiente
├── install/
│   ├── schema.sql          # Estrutura do banco
│   └── index.php           # Página de instalação
└── uploads/                # Arquivos enviados
```

## 🎨 Tecnologias Utilizadas

- **Backend:** PHP 7.4+ com PDO
- **Frontend:** SB Admin 2, Bootstrap 4, jQuery
- **Gráficos:** ApexCharts
- **Banco:** MySQL 5.7+
- **Servidor:** Apache com mod_rewrite

## 📊 Banco de Dados

O sistema utiliza 5 tabelas principais:
- `users` - Usuários do sistema
- `clients` - Clientes cadastrados
- `projects` - Projetos dos clientes
- `payments` - Pagamentos dos projetos
- `goals` - Metas (para futuras implementações)

## 🔧 Configurações Avançadas

### Permissões de Arquivos
- Pastas: 755
- Arquivos: 644
- Pasta uploads/: 777

### Requisitos do Servidor
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache com mod_rewrite habilitado
- Extensões PHP: PDO, PDO_MySQL

## 📝 Notas de Desenvolvimento

- O sistema utiliza prepared statements para segurança
- Senhas são criptografadas com password_hash()
- Validações tanto no frontend quanto no backend
- Sistema de flash messages para feedback ao usuário
- Paginação automática nas listagens

## 🆘 Suporte

Para dúvidas ou problemas:
1. Verifique se o .htaccess está na pasta correta
2. Confirme as credenciais do banco em config/database.php
3. Verifique se o schema.sql foi importado corretamente
4. Confirme que mod_rewrite está habilitado no Apache

## 📄 Licença

Este projeto é de uso livre para fins educacionais e comerciais.
