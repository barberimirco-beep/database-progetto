# database-progetto

Lettore di etichette RFID posizionate su prodotti per esempio nei supermercati.
Questi devono inviare l’ID del prodotto a un database, e questo deve restituire
le informazioni del prodotto collegato a quel ID, come nome e prezzo.
Il database conterrà quindi: ID_Prodotto, Nome, Prezzo, Scadenza.
Queste possono essere utili anche in caso di furto.
Inserimento delle scadenze dai prodotti.
Sito web dove visualizzare lo stoccaggio del magazzino, con tabella con
scritte le scadenze.

Il DB verrà creato sul server della scuola, il codice sql verrà caricato 
anche qui su Github, il Docker servirà per effettuare i test in un ambiente
protetto.


Durante la realizazione del database abbiamo avuto diversi problemi:

-Inanzitutto abbiamo avuto difficoltà a collegare i file qui caricati col docker su portainer, ma dopo varie ricerche con l'IA siamo riusciti a capire che l'errore stava nel fatto che non caricassimo le giuste "Enviroment variables" che si trovano nel file .env nascosto.

-Abbiamo poi incontrato difficoltà nella fase di deploy dello stack, in quanto il docker-compose non poteva caricare automaticamente le cartelle presenti su github a causa di mancanza di permessi. Abbiamo quindi dovuto caricarli manualmente inserendo i file "Dockerfile" presenti nelle cartelle.

-Poi la pagina web e il database non riuscivano a comunicare, siamo quindi andati a creare una network su portainer e siamo andati ad aggiornare il file .yml aggiungendo questa riga "external: true". Dopo ciò continuavano a non riuscire a comunicare, dopo varie revisioni siamo riusciti però a capire che non funzionava siccome db e web non utilizzavano le stesse Envirment variables.
