# Tasks

The part of tasks both Watcher applications hold in common: their states, their types, what a
task is worth reading as, and the actions around them.

What a task *hangs on* is not here. The customer application files a task under a customer and a
contract; the network one files it under an access point. Each application says so by extending
the tables and entities below and adding its own associations to them.

## What the application supplies

| Plugin | Application |
|---|---|
| `Tasks\Model\Table\TaskStatesTable` | `App\Model\Table\TaskStatesTable extends` it |
| `Tasks\Model\Entity\TaskState` | `App\Model\Entity\TaskState extends` it |
| `Tasks\Controller\Trait\TaskStatesControllerTrait` | `App\Controller\TaskStatesController uses` it |

The table locator resolves an alias without a plugin prefix - `TaskStates` - to the application's
class first, so the trait picks up whatever the application made of it without anything being
registered.

Templates come from this plugin, the controller trait pointing the view builder at it. An
application that wants its own puts them under `templates/plugin/Tasks/`.
