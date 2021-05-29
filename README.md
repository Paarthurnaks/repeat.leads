# repeat.leads
Функционал повторных лидов в Битрикс24

Выполняет сквозной анализ лидов на его дубликаты по телефону или email.
Так как функионал разработан для Битрикс24, то помещать его нужно в папку local.

После первой инициализации проекта, в списке пользовательских полей появится новый тип "Кастомное поле" (название по желанию можно изменить)
Необходимо создать поле типа "Кастомное поле" и код поля "UF_CRM_DUPLICATE". Объект "CRM_LEAD".

После того, как поле было создано, функционал начнет свою работу. При открытии карточки лида выберите поле, которое Вы создали для отображения и оно будет отображать информацию в реальном времени.
У всех новых лидов он будет отображаться как положено. У старых лидов поле будет считаться незаполненным и будет отображаться только в режиме редактирования. Для этого стоит единожды выполнить метод updateAllLeads()
