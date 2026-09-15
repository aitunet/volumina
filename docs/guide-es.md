# Guía de Volumina

> Fuente de la guía pública, para publicar en tunetdesign.com. Escrita para
> cualquier sitio, no para uno en concreto. Los nombres de pantallas, campos y
> botones son los que muestra un sitio en español; entre paréntesis, la primera
> vez, el nombre en inglés, por si el sitio está en ese idioma. Cuando esta
> guía se publique, su dirección va en `WelcomeScreen::GUIDE_URL`.

**Volumina 1.0 · WordPress 6.6 o superior · PHP 8.1 o superior**

De un plugin recién instalado a un audiolibro con capítulos que se puede
escuchar, retomar y compartir.

---

## Contenido

1. [Qué es y qué no es](#qué-es-y-qué-no-es)
2. [El menú Audiolibros](#el-menú-audiolibros)
3. [Primer arranque](#primer-arranque-la-bienvenida-y-dos-ajustes)
4. [Tu primer audiolibro, paso a paso](#tu-primer-audiolibro-paso-a-paso)
5. [El reproductor](#el-reproductor-visto-por-quien-escucha)
6. [Quién puede escuchar](#quién-puede-escuchar)
7. [Los tres bloques](#los-tres-bloques)
8. [Géneros y series](#géneros-y-series)
9. [Ajustes y registro](#ajustes-y-registro)
10. [El idioma](#el-idioma)
11. [Si algo no funciona](#si-algo-no-funciona)
12. [Sobre Pro](#sobre-pro)

---

## Qué es y qué no es

Volumina convierte WordPress en una biblioteca de audiolibros. Cada
**audiolibro** es una entrada con portada, narrador y ficha; cada **capítulo**
es un archivo de audio con un título y un lugar dentro del libro. En la página
del libro aparece un reproductor pensado para escuchar durante horas:
capítulos, velocidad, saltos de 15 y 30 segundos, temporizador de apagado y,
lo más importante, **recuerda dónde se quedó cada oyente**.

Lo que no hace, para que no lo busques: **no vende** (no hay carrito ni pagos;
eso es Volumina Pro, un plugin aparte) y **no protege el audio contra copias**.
Ningún reproductor web puede: lo que un navegador reproduce, se puede guardar.
Lo que sí puede hacer es decidir quién ve el reproductor.

## El menú Audiolibros

Tras instalarlo aparece **Audiolibros** (*Audiobooks*) en el menú lateral del
escritorio, con el icono de los cinco lomos. Todo el plugin vive ahí.

| Entrada | Para qué sirve |
|---|---|
| **Todos los audiolibros** (*All Audiobooks*) | La lista de libros, con su género, serie y estado. |
| **Añadir nuevo audiolibro** (*Add New Audiobook*) | Crear un libro. Empieza aquí. |
| **Géneros** y **Series** (*Genres*, *Series*) | Etiquetas para agrupar libros. Funcionan como las categorías de las entradas. |
| **Capítulos** (*Chapters*) | Todos los capítulos de todos los libros, en una lista. Casi nunca hace falta entrar: los capítulos se manejan desde el libro. |
| **Ajustes** (*Settings*) | Siete ajustes en tres grupos. El plugin funciona sin tocar ninguno. |
| **Registro** (*Log*) | Solo aparece si activas el registro en Ajustes. |
| **Pro** | Una página informativa sobre el plugin de pago. No tiene botones ni funciones bloqueadas; es una descripción. Desaparece cuando Pro está instalado. |

Y fuera de ese menú, en **Escritorio › Bienvenido a Volumina** (*Dashboard ›
Welcome to Volumina*), la página que ves al activar el plugin: qué es, los tres
pasos y por dónde seguir. Siempre se puede volver a ella.

Cada pantalla tiene su ayuda en la pestaña **Ayuda** (*Help*) de la esquina
superior derecha, escrita para esa pantalla en concreto. Si dudas de un campo,
mira ahí antes que en esta guía.

## Primer arranque: la bienvenida y dos ajustes

Al activar el plugin, WordPress te lleva a la página de **Bienvenida**: qué es
Volumina, qué no es, y los tres pasos hasta el primer audiolibro. Solo ocurre
la primera vez; después la página sigue en Escritorio.

El botón de la bienvenida —y un aviso azul en las pantallas del plugin:
*«Volumina está instalado. Dos ajustes deciden cómo se comporta»*— te llevan a
**Ajustes**, donde los dos primeros son los que importan:

- **Los audiolibros nuevos son** (*New audiobooks are*) — cómo nace cada
  libro nuevo: **Público — cualquiera puede escucharlo** o **Restringido —
  solo quienes lo hayan recibido**. Cambiarlo después nunca toca los libros ya
  creados; cada libro decide por sí mismo en su pantalla.
- **Páginas de libro** (*Book pages*) — si la página de cada audiolibro debe
  mostrar el reproductor y los capítulos automáticamente. Déjalo activado
  salvo que quieras colocar el bloque **Audiolibro** tú mismo en una plantilla.

Guarda los ajustes una vez y el aviso desaparece para siempre. Si no quieres
tocar nada, el propio aviso ofrece **Mantener los valores por defecto** (*Keep
the defaults*), que hace lo mismo: público y reproductor automático son lo que
la mayoría quiere.

## Tu primer audiolibro, paso a paso

La regla que ordena todo el trabajo: **primero el libro, luego sus capítulos,
uno por archivo de audio**. Un libro se puede publicar sin capítulos (mostrará
su ficha y dirá que aún no tiene ninguno), pero un capítulo no existe sin
libro.

### 1. Crea el libro

**Audiolibros › Añadir nuevo audiolibro**. Se abre el editor de bloques normal
de WordPress. Escribe el **título** y, en el cuerpo, la **descripción** que
leerá el oyente (sinopsis, de qué va, quién lo lee). El **extracto** (panel
lateral) es opcional: una línea para listados.

### 2. Rellena la ficha

La **portada** es la imagen destacada de WordPress: en el panel lateral, arriba
del todo, pulsa **Elegir portada** (*Set cover*) y elígela o súbela. Vertical,
como un libro. Se usa en la página, en el reproductor y en «Seguir
escuchando».

Debajo del editor está la caja **Detalles del audiolibro** (*Audiobook
details*). Ningún campo es obligatorio.

- **Narrador** (*Narrator*) — quién lo lee. **Editorial** (*Publisher*) —
  quién lo edita. **ISBN** — si esta edición tiene.
- **Idioma** (*Language*) — el idioma en que se lee, como código: `es_ES`,
  `es_AR`, `en_US`.
- **Duración total** (*Total duration*) — **déjalo en 0**. El libro muestra la
  suma de sus capítulos. Solo se rellena si quieres decir otra cosa.
- **Quién puede escuchar** (*Who can listen*) — público o restringido. Más
  abajo se explica qué cambia.

### 3. Publica el libro antes de añadir capítulos

Pulsa **Publicar** (o **Guardar borrador** si aún no quieres que se vea). Hasta
que el libro está guardado, la caja de capítulos solo dice *«Guarda primero
este audiolibro y podrás añadir aquí sus capítulos»*.

### 4. Añade el primer capítulo

En la caja **Capítulos** (*Chapters*), pulsa **Añadir un capítulo** (*Add a
chapter*). Se abre la pantalla de capítulo con el libro ya elegido. Ponle
título (*Capítulo 1*, o el nombre del capítulo) y, en **Detalles del
capítulo** (*Chapter details*):

- **Libro** (*Book*) — ya viene puesto. Si creas el capítulo desde
  **Capítulos** en vez de desde el libro, elígelo aquí.
- **Elegir archivo de audio** (*Choose audio file*) — sube el archivo desde tu
  ordenador o elígelo de la biblioteca. **Solo acepta audio.**
- **Duración** (*Duration*) — se rellena sola leyendo el archivo. Si queda en
  0, escríbela a mano en segundos (una hora y cuarto son `4500`).
- **Posición** (*Position*) — informativo. El orden se decide en la pantalla
  del libro.

Pulsa **Publicar**. Los capítulos no necesitan publicarse «aparte»: si el
libro está publicado, sus capítulos se escuchan.

### 5. Repite por cada archivo

Vuelve al libro (en la pantalla del capítulo, el enlace *«Los capítulos se
ordenan en la pantalla del libro»* te lleva de vuelta; o desde **Todos los
audiolibros**) y añade el siguiente. Cada capítulo que crees desde el libro se
coloca al final.

### 6. Ordénalos

En la caja **Capítulos** del libro, **arrastra** un capítulo a su sitio o usa
las flechas **Subir** y **Bajar** (*Up*, *Down*). No hay botón de guardar:
verás *«Orden guardado»* cada vez que muevas uno. El orden que ves es el orden
en que se escucha.

### 7. Míralo

**Ver audiolibro** (*View Audiobook*) abre la página del libro: portada, ficha
(*Narrado por*, *Editorial*, *ISBN*, *Duración*), el reproductor y la lista de
capítulos. Todos los libros publicados aparecen además en `/audiobooks/`, la
página de archivo que WordPress genera con la plantilla de tu tema.

### Sobre los archivos de audio

- **MP3** es la mejor opción: pesa poco y lo reproduce todo. También sirven
  M4A/AAC, OGG y WAV. Un WAV de una hora ronda los 600 MB; el mismo audio en
  MP3 a 96 kbps, unos 40 MB.
- El **tamaño máximo de subida lo pone tu hosting**, no el plugin. Si un
  archivo no sube, el límite está en el servidor (en **Medios › Añadir nuevo**
  verás el «Tamaño máximo de subida de archivos»). Un capítulo por archivo es
  precisamente lo que hace que no necesites archivos gigantes.
- Un capítulo **sin archivo** se queda en la lista de la página, pero el
  reproductor lo salta.
- Si borras un archivo de la biblioteca, el capítulo sigue existiendo y su
  audio da error. Cámbialo por otro desde la pantalla del capítulo.

## El reproductor, visto por quien escucha

Está diseñado primero para el teléfono. Todo se maneja también con teclado y
funciona con lectores de pantalla.

| Control | Qué hace |
|---|---|
| **Reproducir / Pausar** | Lo de siempre. La primera vez que un oyente vuelve a un libro ve *«Listo para seguir donde lo dejaste. Pulsa reproducir»* y arranca donde lo dejó. |
| **Capítulo anterior · Capítulo siguiente** | Saltan de capítulo. Al terminar uno, sigue solo con el siguiente; al terminar el último, para y avisa. |
| **−15 s · +30 s** | Atrás 15 segundos, adelante 30: los saltos con que se escucha un libro, no una canción. |
| **Posición** | La barra del capítulo actual. Se puede arrastrar; el audio salta cuando sueltas. |
| **Velocidad** (*Speed*) | Seis velocidades: 0,75, 1, 1,25, 1,5, 1,75 y 2. La voz mantiene el tono. |
| **Temporizador de apagado** (*Sleep timer*) | Apaga a los 5, 10, 15, 30, 45 o 60 minutos, o al **fin del capítulo**. Cuando salta, la reproducción se detiene y el sitio lo anuncia. |

### Dónde se guarda «por dónde iba»

- **Usuario con sesión iniciada**: en su cuenta, en el servidor. Empieza en el
  móvil y sigue en el ordenador; la posición es la misma.
- **Visitante sin cuenta**: en su propio navegador. Le funciona igual, pero
  solo en ese dispositivo. Nada suyo llega al servidor.

Al cambiar de página, de capítulo o al cerrar la pestaña, la posición ya está
guardada: se anota en cada pausa, cada salto, cada diez segundos mientras suena
y al salir de la página.

## Quién puede escuchar

Cada libro tiene un interruptor **Quién puede escuchar** en su ficha, con dos
posiciones:

- **Público** — cualquiera que llegue a la página escucha.
- **Restringido** — la página sigue existiendo, con portada, ficha y lista de
  capítulos, pero **solo se retira el audio**. Un visitante ve *«Inicia sesión
  para escuchar este audiolibro»*; un usuario sin acceso, *«Este audiolibro no
  está disponible en tu cuenta»*. Quien puede editar el libro siempre lo
  escucha, para poder comprobarlo.

> **Ojo.** En la versión gratuita todavía no hay una pantalla para dar acceso
> a un usuario concreto. El mecanismo existe y funciona, pero solo se alcanza
> desde código o desde WP-CLI. Hasta que exista esa pantalla, para darle un
> libro restringido a alguien:
>
> ```
> wp eval 'TUNET\Volumina\Storage\Grants::grant( ID_USUARIO, ID_LIBRO );'
> wp eval 'TUNET\Volumina\Storage\Grants::revoke( ID_USUARIO, ID_LIBRO );'
> ```
>
> Los ID se ven en la URL al editar el usuario (`user_id=`) y el libro
> (`post=`). Vender libros —que es el caso real de «restringido»— es trabajo
> de Volumina Pro, con WooCommerce o Easy Digital Downloads decidiendo quién
> pagó.

## Los tres bloques

Con **Páginas de libro** activado no necesitas ningún bloque: cada página de
audiolibro ya trae todo. Los bloques sirven para poner audiolibros **en otras
páginas**: una portada, una página de «biblioteca», la home. Se buscan en el
editor escribiendo `/audiolibro`, `/capítulos` o `/seguir` (en inglés,
`/audiobook`, `/chapter`, `/continue`).

| Bloque | Qué muestra | Opciones (panel lateral) |
|---|---|---|
| **Audiolibro** (*Audiobook*) | Un libro completo: portada, ficha, reproductor y capítulos. | **Audiolibro**: elige el libro, o «el libro del que trata esta página» si el bloque va en la plantilla de audiolibro. **Partes que se muestran**: Portada, Ficha, Reproductor, Capítulos, Encabezado de capítulos. |
| **Lista de capítulos** (*Chapter list*) | Solo la lista de capítulos, en orden. | Libro, y **Duraciones** para mostrar u ocultar la de cada uno. |
| **Seguir escuchando** (*Continue listening*) | Los libros que este oyente ha empezado y por dónde va, con enlace para seguir. A quien no ha empezado ninguno le dice *«Todavía no has empezado nada»*. | **Libros que mostrar** (3 por defecto) y **Portadas**. |

Las vistas previas en el editor son el mismo HTML que verá el visitante, así
que lo que ves ahí es lo que sale. Si pones el bloque **Audiolibro** en la
plantilla de audiolibros de tu tema, apaga **Páginas de libro** en Ajustes;
si no, el plugin lo evita solo, pero es más claro decidirlo.

## Géneros y series

Son dos etiquetas normales de WordPress. **Género** es el tipo (novela,
poesía, teatro…); **serie** agrupa libros que van juntos (una saga, una
colección, «Clásicos en español»). Se asignan desde el panel lateral del
editor del libro, igual que las categorías de una entrada, y se ven como
columnas en **Todos los audiolibros**. Cada una tiene su página de archivo en
el sitio (`/genre/novela/`, `/series/clasicos-en-espanol/`), útil para menús.

## Ajustes y registro

**Audiolibros › Ajustes**. Siete ajustes en tres grupos, cada uno con su
explicación debajo.

**Escucha** (*Listening*):

- **Los audiolibros nuevos son** — el valor con que nace un libro nuevo. Nunca
  cambia uno existente.
- **Páginas de libro** — reproductor y capítulos automáticos en la página del
  libro.

**Presentación** (*Presentation*), cómo se ve un audiolibro en la página:

- **Plantilla** (*Template*) — cómo se dispone y se viste: portada, ficha,
  reproductor y capítulos. Volumina trae una, *Básica*; una extensión puede
  añadir más.
- **Portada** (*Cover*) — al lado de la ficha, encima, o sin mostrar. En una
  pantalla estrecha, «al lado» pasa a «encima» cuando no cabe.
- **Color de acento** (*Accent colour*) — un color de la paleta de tu tema para
  el botón de reproducir, la barra de progreso y el capítulo en curso. Nunca
  para texto. Sin ninguno, el reproductor se dibuja en el color del texto.

Los tres se pueden cambiar también bloque a bloque, en el panel **Presentación**
del bloque *Audiolibro*.

**Mantenimiento** (*Housekeeping*):

- **Registro** (*Log*) — activa un registro de sucesos notables. Enciéndelo
  cuando algo falle y apágalo después.
- **Al desinstalar** (*On uninstall*) — si al **borrar** el plugin (no al
  desactivarlo) debe llevarse también sus tablas y ajustes. Apagado, una
  reinstalación encuentra a cada oyente donde lo dejó.

Con el registro activo aparece **Audiolibros › Registro**: guarda los últimos
200 sucesos, como un oyente rechazado en un libro restringido o un archivo de
audio que ya no está donde el capítulo dice. No registra quién escucha qué.
**Vaciar el registro** (*Empty the log*) lo vacía.

> **Nota.** Desactivar el plugin no borra nada. Los libros y capítulos son
> entradas: van a la papelera como cualquier otra y se recuperan desde ahí.

## El idioma

El plugin viene en inglés y traducido, completo, al español y al portugués de
Brasil. Toma el idioma del sitio: **Ajustes › Generales › Idioma del sitio**
(*Settings › General › Site Language*). Si tu sitio está en inglés y quieres
verlo en español, elige *Español* ahí; WordPress descarga su propio paquete de
idioma, y el de Volumina ya está incluido. Cada usuario puede además elegir su
idioma en su perfil, y el escritorio le hará caso.

## Si algo no funciona

- **La página del libro da «Página no encontrada».** WordPress no ha registrado
  las direcciones nuevas. Ve a **Ajustes › Enlaces permanentes** y pulsa
  **Guardar cambios** sin cambiar nada.
- **No aparece el reproductor.** O el libro no tiene ningún capítulo con audio,
  o **Páginas de libro** está apagado, o el libro es restringido y no has
  iniciado sesión.
- **El archivo no sube.** Límite del hosting. Conviértelo a MP3 o pide subir el
  límite (`upload_max_filesize`).
- **La duración sale 0.** WordPress no pudo leer el archivo (pasa con algunos
  formatos raros). Escríbela a mano en **Duración**, en segundos.
- **Salta o no avanza al arrastrar.** Algunos hostings desactivan las
  «peticiones de rango» en su proxy; el audio suena, pero no se puede saltar ni
  retomar. Activa el **Registro** y pregúntale al hosting por *HTTP range
  requests*.
- **Una posición no se guardó.** Si el oyente está sin sesión, la posición vive
  en su navegador; al borrar datos de navegación se pierde. Con cuenta, no.

## Sobre Pro

La pantalla **Audiolibros › Pro** describe el plugin de pago, que se instala
aparte y amplía este: venta con WooCommerce o Easy Digital Downloads, enlaces
de audio firmados que caducan, series como un solo producto, plantillas y
estilos, e informes de escucha. En el plugin gratuito esa pantalla es solo
texto e ilustraciones, a propósito: el directorio de WordPress no permite
incluir funciones bloqueadas a la espera de un pago, y no las hay. Cuando Pro
no está instalado, la pantalla se ve; cuando lo está, desaparece.

---

Volumina · TUNET · tunetdesign.com
