import { Head, router, useForm } from '@inertiajs/react'
import { ListTodo } from 'lucide-react'
import { useState } from 'react'
import { store as storeProject } from '@/actions/App/Http/Controllers/ProjectController'
import {
  index,
  store
} from '@/actions/App/Http/Controllers/TaskController'
import reorder from '@/actions/App/Http/Controllers/TaskOrderController'
import InputError from '@/components/input-error'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import TaskRow from '@/components/task-row'

type Task = {
  id: number
  name: string
  priority: number
  created_at: string
  updated_at: string
}
type Project = {
  id: number
  name: string
}

export default function TaskBoard({
  projects,
  tasks,
  selectedProjectId,
}: {
  projects: Project[]
  tasks: Task[]
  selectedProjectId: number | null
}) {
  const taskForm = useForm({ name: '' })
  const projectForm = useForm({ name: '' })
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState('')
  const selectedProject = projects.find(
    (project) => project.id === selectedProjectId,
  )

  function move(from: number, to: number) {
    if (busy || selectedProjectId === null || from === to || from < 0 || to < 0)
      return
    const ids = tasks.map((task) => task.id)
    const [moved] = ids.splice(from, 1)
    ids.splice(to, 0, moved)
    setBusy(true)
    setMessage('Saving order…')
    router.patch(
      reorder.url(selectedProjectId),
      { task_ids: ids },
      {
        preserveScroll: true,
        onSuccess: () => setMessage('Order saved.'),
        onError: (errors) =>
          setMessage(
            errors.task_ids ?? 'Unable to save order. Please try again.',
          ),
        onFinish: () => setBusy(false),
      },
    )
  }

  return (
    <main className="min-h-screen bg-background px-4 py-12 text-foreground sm:px-8">
      <Head title="Task manager" />
      <div className="mx-auto max-w-3xl space-y-8">
        <header className="space-y-3">
          <div className="flex items-center gap-3">
            <ListTodo className="size-8 text-primary" />
            <h1 className="text-3xl font-semibold tracking-tight">
              Task manager
            </h1>
          </div>
          <p className="text-muted-foreground">
            A little order for everything you need to do.
          </p>
        </header>
        <section
          className="grid gap-5 rounded-xl border bg-card p-5 sm:grid-cols-2"
          aria-label="Projects"
        >
          <div className="space-y-2">
            <Label htmlFor="project">Current project</Label>
            <select
              id="project"
              className="h-9 w-full rounded-md border bg-background px-3 text-sm"
              disabled={busy || taskForm.processing}
              value={selectedProjectId ?? ''}
              onChange={(event) => {
                taskForm.reset()
                taskForm.clearErrors()
                setMessage('')
                router.get(
                  index.url({
                    query: { project: event.target.value },
                  }),
                )
              }}
            >
              {projects.length === 0 && (
                <option value="">Create your first project</option>
              )}
              {projects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </select>
          </div>
          <form
            className="space-y-2"
            onSubmit={(event) => {
              event.preventDefault()
              projectForm.post(storeProject.url(), {
                onSuccess: () => projectForm.reset(),
              })
            }}
          >
            <Label htmlFor="project-name">New project</Label>
            <div className="flex gap-2">
              <Input
                id="project-name"
                placeholder="Project name"
                required
                maxLength={255}
                value={projectForm.data.name}
                onChange={(event) =>
                  projectForm.setData('name', event.target.value)
                }
              />
              <Button disabled={projectForm.processing || busy}>Create</Button>
            </div>
            <InputError message={projectForm.errors.name} />
          </form>
        </section>
        <section
          className="overflow-hidden rounded-xl border bg-card"
          aria-labelledby="tasks-heading"
        >
          <div className="space-y-4 border-b p-5">
            <div className="flex items-center justify-between gap-3">
              <h2 id="tasks-heading" className="text-xl font-semibold">
                {selectedProject?.name ?? 'Your tasks'}
              </h2>
              <span className="text-sm text-muted-foreground">
                {tasks.length} {tasks.length === 1 ? 'task' : 'tasks'}
              </span>
            </div>
            <p className="text-sm text-muted-foreground">
              Drag the handle to reorder. Priority #1 goes first. Use the arrows
              on a keyboard or touch screen.
            </p>
            {selectedProjectId !== null && (
              <form
                className="space-y-2"
                onSubmit={(event) => {
                  event.preventDefault()
                  taskForm.post(store.url(selectedProjectId), {
                    preserveScroll: true,
                    onSuccess: () => taskForm.reset(),
                  })
                }}
              >
                <Label htmlFor="task-name">Add a task</Label>
                <div className="flex gap-2">
                  <Input
                    id="task-name"
                    placeholder="What needs to get done?"
                    required
                    maxLength={255}
                    value={taskForm.data.name}
                    onChange={(event) =>
                      taskForm.setData('name', event.target.value)
                    }
                  />
                  <Button disabled={taskForm.processing || busy}>
                    Add task
                  </Button>
                </div>
                <InputError message={taskForm.errors.name} />
              </form>
            )}
          </div>
          {tasks.length === 0 ? (
            <div className="p-12 text-center text-muted-foreground">
              {selectedProjectId === null
                ? 'Create a project to start organizing your tasks.'
                : 'No tasks yet. Add your first task above.'}
            </div>
          ) : (
            <ol>
              {tasks.map((task, position) => (
                <TaskRow
                  key={task.id}
                  task={task}
                  projectId={selectedProjectId!}
                  position={position}
                  total={tasks.length}
                  busy={busy}
                  onMove={move}
                  onDrop={(draggedId, targetId) => {
                    move(
                      tasks.findIndex((item) => item.id === draggedId),
                      tasks.findIndex((item) => item.id === targetId),
                    )
                  }}
                />
              ))}
            </ol>
          )}
        </section>
        <p
          role="status"
          aria-live="polite"
          className="min-h-5 text-sm text-muted-foreground"
        >
          {message}
        </p>
      </div>
    </main>
  )
}
