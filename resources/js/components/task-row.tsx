import { useState } from 'react'
import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import InputError from '@/components/input-error'
import { GripVertical } from 'lucide-react'
import { ArrowUp, ArrowDown, Pencil, Trash2 } from 'lucide-react'
import { router } from '@inertiajs/react'
import {
  index,
  store,
  update,
  destroy,
} from '@/actions/App/Http/Controllers/TaskController'
import type { Task } from '@/types/models'

export default function TaskRow({
  task,
  projectId,
  busy,
  position,
  total,
  onMove,
  onDrop,
}: {
  task: Task
  projectId: number
  busy: boolean
  position: number
  total: number
  onMove: (from: number, to: number) => void
  onDrop: (draggedId: number, targetId: number) => void
}) {
  const [editing, setEditing] = useState(false)
  const form = useForm({ name: task.name })
  const [deleting, setDeleting] = useState(false)
  return (
    <li
      className="flex flex-wrap items-center gap-3 border-b p-4 last:border-b-0"
      onDragOver={(event) => event.preventDefault()}
      onDrop={(event) => {
        event.preventDefault()
        onDrop(Number(event.dataTransfer.getData('text/plain')), task.id)
      }}
    >
      <button
        type="button"
        draggable={!busy && !editing && !deleting}
        onDragStart={(event) => {
          event.dataTransfer.setData('text/plain', String(task.id))
          event.dataTransfer.effectAllowed = 'move'
        }}
        aria-label={`Drag ${task.name} to reorder`}
        className="cursor-grab text-muted-foreground active:cursor-grabbing"
      >
        <GripVertical className="size-5" />
      </button>
      <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-semibold">
        {task.priority}
      </span>
      {editing ? (
        <form
          className="flex min-w-0 flex-1 flex-wrap gap-2"
          onSubmit={(event) => {
            event.preventDefault()
            form.patch(update.url({ project: projectId, task: task.id }), {
              preserveScroll: true,
              onSuccess: () => setEditing(false),
            })
          }}
        >
          <div className="min-w-40 flex-1">
            <Input
              aria-label="Task name"
              autoFocus
              maxLength={255}
              value={form.data.name}
              onChange={(event) => form.setData('name', event.target.value)}
              required
            />
            <InputError message={form.errors.name} />
          </div>
          <Button disabled={form.processing}>Save</Button>
          <Button
            type="button"
            variant="ghost"
            disabled={form.processing}
            onClick={() => {
              form.reset()
              form.clearErrors()
              setEditing(false)
            }}
          >
            Cancel
          </Button>
        </form>
      ) : (
        <>
          <div className="min-w-0 flex-1">
            <p className="font-medium break-words">{task.name}</p>
            <p className="text-xs text-muted-foreground">
              Created {new Date(task.created_at).toLocaleDateString()} · Updated{' '}
              {new Date(task.updated_at).toLocaleDateString()}
            </p>
          </div>
          <div className="flex gap-1">
            <Button
              variant="ghost"
              size="icon"
              aria-label={`Move ${task.name} up`}
              disabled={busy || position === 0 || deleting}
              onClick={() => onMove(position, position - 1)}
            >
              <ArrowUp className="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              aria-label={`Move ${task.name} down`}
              disabled={busy || position === total - 1 || deleting}
              onClick={() => onMove(position, position + 1)}
            >
              <ArrowDown className="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              aria-label={`Edit ${task.name}`}
              disabled={busy || deleting}
              onClick={() => {
                form.setData('name', task.name)
                setEditing(true)
              }}
            >
              <Pencil className="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              aria-label={`Delete ${task.name}`}
              disabled={busy || deleting}
              onClick={() => {
                if (window.confirm(`Delete “${task.name}”?`)) {
                  setDeleting(true)
                  router.delete(
                    destroy.url({
                      project: projectId,
                      task: task.id,
                    }),
                    {
                      preserveScroll: true,
                      onFinish: () => setDeleting(false),
                    },
                  )
                }
              }}
            >
              <Trash2 className="size-4 text-destructive" />
            </Button>
          </div>
        </>
      )}
    </li>
  )
}