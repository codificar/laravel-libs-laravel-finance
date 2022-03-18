# Refatoração

- [ ] Remover o LibModel (trocar por repositório) - Refazer todas as queries, estão todas bem avacalhadas
- [ ] src/Http/Controllers/FinanceController.php função providerExtract não está comentada, chama uma outra e ainda faz um foreach para trazer os saldos. Fazer tudo em uma unica query no repositório em questão
- [ ] Reescrever código dos controllers