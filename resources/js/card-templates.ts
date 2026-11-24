export interface CardTemplate {
  id: number
  name: string
  title: string
  description: string | null
}

export function cardTemplatesPath(boardId: number) {
  return '/api/boards/' + boardId + '/card-templates'
}
